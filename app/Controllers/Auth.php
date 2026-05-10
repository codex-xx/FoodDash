<?php

namespace App\Controllers;

use App\Libraries\ActivityLogger;
use App\Libraries\SecurityAuditService;
use App\Models\UserModel;
use CodeIgniter\Controller;

class Auth extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        helper(['form', 'url']);
    }

    // Show login form
    public function login()
    {
        return view('auth/login');
    }

    // Process login POST
    public function attempt()
    {
        $logger = new ActivityLogger();
        $security = new SecurityAuditService();

        if ($security->isRequestBlocked($this->request)) {
            $security->logEvent(
                $this->request,
                null,
                'intrusion_attempt',
                'Blocked IP attempted to login',
                'critical'
            );

            return redirect()->back()->withInput()->with('error', 'Too many suspicious attempts detected. Try again later.');
        }

        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]'
        ];

        if (! $this->validate($rules)) {
            $submittedEmail = (string) ($this->request->getPost('email') ?? '');
            $logger->logLoginAttempt($this->request, null, null, $submittedEmail, false, 'validation_failed');
            $result = $security->recordFailedLogin($this->request, null, 'validation_failed', $submittedEmail);
            $this->registerFailedAttemptForSession();
            $this->appendSecurityWarning($result);
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $maxAttempts = 3;

        $user = $this->userModel->where('email', $email)->first();

        if (! $user) {
            $logger->logLoginAttempt($this->request, null, null, (string) $email, false, 'user_not_found');
            $result = $security->recordFailedLogin($this->request, null, 'user_not_found', (string) $email);
            $this->registerFailedAttemptForSession();
            $this->appendSecurityWarning($result);
            session()->setFlashdata('security_warning', 'Security notice: Unsuccessful sign-in detected. If this was not you, reset your password immediately.');
            return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
        }

        // Enforce account lockout before password verification.
        if (! empty($user['locked_until'])) {
            $lockedUntilTs = strtotime((string) $user['locked_until']);
            if ($lockedUntilTs !== false && $lockedUntilTs > time()) {
                $remainingSeconds = $lockedUntilTs - time();
                $remainingMessage = $this->formatRemainingLockTime($remainingSeconds);

                $logger->logLoginAttempt($this->request, (string) ($user['role'] ?? 'user'), (int) $user['id'], (string) $email, false, 'locked_account_access_attempt');
                $security->logEvent(
                    $this->request,
                    (int) $user['id'],
                    'locked_account_access_attempt',
                    'Login attempt blocked because account is temporarily locked',
                    'high',
                    [
                        'locked_until' => (string) $user['locked_until'],
                        'remaining_seconds' => $remainingSeconds,
                    ]
                );

                session()->setFlashdata('security_warning', 'Security protection active: A sign-in was attempted while this account is temporarily locked.');
                return redirect()->back()->withInput()->with('error', 'Too many failed login attempts. Please try again later. ' . $remainingMessage);
            }

            // Lock has expired, so reset counters and allow a fresh attempt cycle.
            $this->userModel->update((int) $user['id'], [
                'failed_attempts' => 0,
                'locked_until' => null,
            ]);

            $user['failed_attempts'] = 0;
            $user['locked_until'] = null;
        }

        if (! password_verify($password, $user['password'])) {
            $logger->logLoginAttempt($this->request, (string) ($user['role'] ?? 'user'), (int) $user['id'], (string) $email, false, 'invalid_password');
            $result = $security->recordFailedLogin($this->request, (int) $user['id'], 'invalid_password', (string) $email);
            $this->registerFailedAttemptForSession();
            $this->appendSecurityWarning($result);

            $failedAttempts = (int) ($user['failed_attempts'] ?? 0) + 1;

            if ($failedAttempts >= $maxAttempts) {
                $newLockCount = (int) ($user['lock_count'] ?? 0) + 1;
                $lockMinutes = $this->resolveProgressiveLockMinutes($newLockCount);
                $lockedUntil = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));

                $this->userModel->update((int) $user['id'], [
                    'failed_attempts' => $maxAttempts,
                    'locked_until' => $lockedUntil,
                    'lock_count' => $newLockCount,
                ]);

                session()->setFlashdata('security_warning', 'Security alert: Multiple invalid password attempts were detected. Temporary account lock has been applied.');
                return redirect()->back()->withInput()->with('error', 'Too many failed login attempts. Please try again later. Account locked for ' . $lockMinutes . ' minute(s).');
            }

            $remainingAttempts = $maxAttempts - $failedAttempts;
            $this->userModel->update((int) $user['id'], [
                'failed_attempts' => $failedAttempts,
                'locked_until' => null,
            ]);

            if ($remainingAttempts <= 1) {
                session()->setFlashdata('security_warning', 'Security warning: One remaining attempt before temporary account lock is activated.');
            } else {
                session()->setFlashdata('security_warning', 'Security notice: Invalid password attempt detected. Review your credentials before trying again.');
            }

            return redirect()->back()->withInput()->with('error', 'Invalid password. You have ' . $remainingAttempts . ' login attempt(s) remaining before temporary account lock.');
        }

        if (! (int) $user['is_active']) {
            $logger->logLoginAttempt($this->request, (string) ($user['role'] ?? 'user'), (int) $user['id'], (string) $email, false, 'account_disabled');
            $security->logEvent(
                $this->request,
                (int) $user['id'],
                'account_disabled_login_attempt',
                'Valid credentials provided, but account is disabled',
                'medium'
            );

            return redirect()->back()->withInput()->with('error', 'Account is currently disabled. Please contact support or wait for approval.');
        }

        // Successful login resets account lockout counters.
        $this->userModel->update((int) $user['id'], [
            'failed_attempts' => 0,
            'locked_until' => null,
        ]);

        // Successful login: create session
        session()->regenerate(true);
        $sessionData = [
            'isLoggedIn'    => true,
            'user_id'       => $user['id'],
            'email'         => $user['email'],
            'role'          => $user['role'],
            'last_activity' => time(),
        ];

        // if the user is a restaurant owner, look up the related restaurant record
        if ($user['role'] === 'restaurant') {
            $restaurantModel = new \App\Models\RestaurantModel();
            $restaurant = $restaurantModel->where('user_id', $user['id'])->first();
            if ($restaurant) {
                $sessionData['restaurant_id']   = $restaurant['id'];
                $sessionData['restaurant_name'] = $restaurant['name'];
            }
        }

        session()->set($sessionData);

        $role = (string) ($user['role'] ?? 'user');

        $sessionJti = $this->persistWebSessionToken((string) $role, (int) $user['id']);
        if ($sessionJti !== null) {
            session()->set('session_jti', $sessionJti);
        }

        $logger->logLoginAttempt($this->request, $role, (int) $user['id'], (string) $email, true, null);
        $security->recordSuccessfulLogin($this->request, (int) $user['id']);
        $this->clearFailedAttemptSession();
        $logger->logUserActivity(
            $this->request,
            $role,
            (int) $user['id'],
            'session_login',
            'users',
            (int) $user['id'],
            ['auth_channel' => 'web']
        );

        // Role-based redirect
        if ($user['role'] === 'admin') {
            return redirect()->to('/dashboard/admin');
        }

        return redirect()->to('/dashboard/restaurant');
    }

    public function logout()
    {
        $session = session();
        $userId = $session->get('user_id');
        $role = (string) ($session->get('role') ?? 'user');

        if (is_numeric($userId)) {
            $logger = new ActivityLogger();
            $logger->logUserActivity(
                $this->request,
                $role,
                (int) $userId,
                'session_logout',
                'users',
                (int) $userId,
                ['auth_channel' => 'web']
            );
        }

        $this->revokeWebSessionToken((string) ($session->get('session_jti') ?? ''));

        $this->clearFailedAttemptSession();

        session()->destroy();
        return redirect()->to('/login')->with('success', 'Logged out');
    }

    protected function tableExists(string $table): bool
    {
        try {
            return db_connect()->tableExists($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function persistWebSessionToken(string $userType, int $userId): ?string
    {
        if (! $this->tableExists('auth_tokens')) {
            return null;
        }

        $sessionId = (string) session_id();
        if ($sessionId === '') {
            return null;
        }

        $jti = 'web_' . $sessionId;
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        try {
            $db = db_connect();
            $table = $db->table('auth_tokens');

            $idColumn = $db->fieldExists('jti', 'auth_tokens') ? 'jti' : 'jwt_id';
            $typeColumn = $db->fieldExists('user_type', 'auth_tokens') ? 'user_type' : 'actor_type';
            $userIdColumn = $db->fieldExists('user_id', 'auth_tokens') ? 'user_id' : 'actor_id';
            $issuedColumn = $db->fieldExists('issued_at', 'auth_tokens') ? 'issued_at' : 'created_at';

            $payload = [
                $typeColumn => $userType,
                $userIdColumn => $userId,
                $idColumn => $jti,
                'token_hash' => hash('sha256', $sessionId),
                $issuedColumn => $now,
                'expires_at' => $expiresAt,
                'last_seen_at' => $now,
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => substr((string) $this->request->getUserAgent(), 0, 255),
                'revoked_at' => null,
            ];

            $existing = $db->table('auth_tokens')
                ->where($idColumn, $jti)
                ->get()
                ->getRowArray();

            if ($existing) {
                $table->where('id', (int) $existing['id'])->update($payload);
            } else {
                $table->insert($payload);
            }

            return $jti;
        } catch (\Throwable $e) {
            log_message('error', 'Failed to persist web session token: ' . $e->getMessage());
            return null;
        }
    }

    protected function revokeWebSessionToken(string $jti): void
    {
        if ($jti === '' || ! $this->tableExists('auth_tokens')) {
            return;
        }

        try {
            $db = db_connect();
            $idColumn = $db->fieldExists('jti', 'auth_tokens') ? 'jti' : 'jwt_id';

            $db->table('auth_tokens')
                ->where($idColumn, $jti)
                ->update([
                    'revoked_at' => date('Y-m-d H:i:s'),
                ]);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to revoke web session token: ' . $e->getMessage());
        }
    }

    protected function ensureCaptchaChallenge(): array
    {
        $question = (string) (session()->get('login_captcha_question') ?? '');
        $answer = (string) (session()->get('login_captcha_answer') ?? '');

        if ($question !== '' && $answer !== '') {
            return ['question' => $question, 'answer' => $answer];
        }

        $left = random_int(1, 9);
        $right = random_int(1, 9);
        $question = $left . ' + ' . $right . ' = ?';
        $answer = (string) ($left + $right);

        session()->set('login_captcha_question', $question);
        session()->set('login_captcha_answer', $answer);

        return ['question' => $question, 'answer' => $answer];
    }

    protected function appendSecurityWarning(array $result): void
    {
        if (! ($result['captcha_required'] ?? false) && ! ($result['alert_raised'] ?? false) && ! ($result['blocked'] ?? false)) {
            return;
        }

        $messages = [];
        if ($result['captcha_required'] ?? false) {
            $messages[] = 'Additional verification is now required for this IP.';
        }

        if ($result['alert_raised'] ?? false) {
            $messages[] = 'Security alert triggered and logged for admin review.';
        }

        if ($result['blocked'] ?? false) {
            $messages[] = 'IP temporarily blocked due to suspicious retries.';
        }

        if ($messages !== []) {
            session()->setFlashdata('security_warning', implode(' ', $messages));
        }
    }

    protected function registerFailedAttemptForSession(): void
    {
        $now = time();
        $window = 300;
        $count = (int) (session()->get('login_failed_count') ?? 0);
        $start = (int) (session()->get('login_failed_window_start') ?? 0);

        if ($start === 0 || ($now - $start) > $window) {
            $start = $now;
            $count = 0;
        }

        $count++;
        session()->set('login_failed_count', $count);
        session()->set('login_failed_window_start', $start);
    }

    protected function requiresCaptchaForSession(): bool
    {
        $now = time();
        $window = 300;
        $threshold = 3;
        $count = (int) (session()->get('login_failed_count') ?? 0);
        $start = (int) (session()->get('login_failed_window_start') ?? 0);

        if ($start === 0 || ($now - $start) > $window) {
            session()->remove('login_failed_count');
            session()->remove('login_failed_window_start');
            session()->remove('login_captcha_answer');
            session()->remove('login_captcha_question');
            return false;
        }

        return $count >= $threshold;
    }

    protected function clearFailedAttemptSession(): void
    {
        session()->remove('login_failed_count');
        session()->remove('login_failed_window_start');
        session()->remove('login_captcha_answer');
        session()->remove('login_captcha_question');
    }

    /**
     * Progressive lock durations in minutes:
     * 1st lock = 1, 2nd lock = 15, 3rd lock = 30, then +15 for each next cycle.
     */
    protected function resolveProgressiveLockMinutes(int $lockCount): int
    {
        if ($lockCount <= 1) {
            return 1;
        }

        if ($lockCount === 2) {
            return 15;
        }

        if ($lockCount === 3) {
            return 30;
        }

        return 30 + (($lockCount - 3) * 15);
    }

    protected function formatRemainingLockTime(int $seconds): string
    {
        $seconds = max(1, $seconds);
        $minutes = (int) ceil($seconds / 60);

        return 'Please wait ' . $minutes . ' minute(s) before trying again.';
    }

    // Show forgot password form
    public function forgot()
    {
        return view('auth/forgot');
    }

    // Handle forgot password POST
    public function sendReset()
    {
        $email = $this->request->getPost('email');

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Please provide a valid email');
        }

        $user = $this->userModel->where('email', $email)->first();

        if (! $user) {
            // Do not reveal that the email is missing — keep generic message
            return redirect()->back()->with('success', 'If that email exists we sent a reset link');
        }

        $token = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        $this->userModel->update($user['id'], [
            'reset_token'   => $token,
            'reset_expires' => $expires,
        ]);

        $resetLink = site_url("reset/" . $token);

        // Send email using PHPMailer
        try {
            $emailService = new \App\Libraries\EmailService();
            $sent = $emailService->sendPasswordResetLink(
                $user['email'],
                $user['name'] ?? 'User',
                $resetLink
            );
            
            if ($sent) {
                $message = 'Password reset link sent to your email.';
            } else {
                $message = 'Failed to send email. Use this link (development): ' . $resetLink;
            }
        } catch (\Exception $e) {
            log_message('error', 'Email send error: ' . $e->getMessage());
            $message = 'Password reset created. Use this link (development): ' . $resetLink;
        }

        return redirect()->to('/login')->with('success', $message);
    }

    // Show reset form (token in URL)
    public function reset($token = null)
    {
        if (! $token) {
            return redirect()->to('/login')->with('error', 'Invalid reset token');
        }

        $user = $this->userModel->where('reset_token', $token)
            ->where('reset_expires >=', date('Y-m-d H:i:s'))
            ->first();

        if (! $user) {
            return redirect()->to('/login')->with('error', 'Reset token invalid or expired');
        }

        return view('auth/reset', ['token' => $token]);
    }

   
    // Process password reset
    public function resetPassword($token = null)
    {
        if (! $token) {
            return redirect()->to('/login')->with('error', 'Invalid reset token');
        }

        $rules = [
            'password'     => 'required|min_length[8]',
            'pass_confirm' => 'matches[password]'
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $user = $this->userModel->where('reset_token', $token)
            ->where('reset_expires >=', date('Y-m-d H:i:s'))
            ->first();

        if (! $user) {
            return redirect()->to('/login')->with('error', 'Reset token invalid or expired');
        }

        $this->userModel->update($user['id'], [
            'password'      => $this->request->getPost('password'),
            'reset_token'   => null,
            'reset_expires' => null,
        ]);

        return redirect()->to('/login')->with('success', 'Password has been reset. You may now login.');
    }

    // Show Help Centre page
    public function help()
    {
        return view('auth/help');
    }

    // Show Be Our Partner page
    public function partner()
    {
        return view('auth/partner');
    }

    // Handle partner registration (driver or restaurant)
    public function submitPartnerRegistration()
    {
        $partnerType = $this->request->getPost('partner_type');
        
        if ($partnerType === 'driver') {
            // Validate driver application
            $rules = [
                'driver_name'    => 'required|min_length[3]',
                'driver_email'   => 'required|valid_email',
                'driver_phone'   => 'required|min_length[10]',
                'vehicle_type'   => 'required',
                'license_number' => 'required|min_length[3]|max_length[50]',
                'driver_terms'   => 'required',
            ];

            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('driver_error', $this->validator->getErrors());
            }

            // Check if email already exists
            $existingUser = $this->userModel->where('email', $this->request->getPost('driver_email'))->first();
            if ($existingUser) {
                return redirect()->back()->withInput()->with('driver_error', 'Email already registered');
            }

            // Generate a random password (they can reset it later)
            $tempPassword = bin2hex(random_bytes(8));

            // Create user account
            $userId = $this->userModel->insert([
                'email'      => $this->request->getPost('driver_email'),
                'password'   => $tempPassword,
                'role'       => 'driver',
                'is_active'  => 0, // Not active until approved
            ]);

            if (!$userId) {
                return redirect()->back()->withInput()->with('driver_error', 'Failed to create account. Please try again.');
            }

            // Create driver record
            $driverModel = new \App\Models\DriverModel();
            $driverData = [
                'user_id'      => $userId,
                'name'         => $this->request->getPost('driver_name'),
                'email'        => $this->request->getPost('driver_email'),
                'phone'        => $this->request->getPost('driver_phone'),
                'vehicle_type' => $this->request->getPost('vehicle_type'),
                'license_number' => $this->request->getPost('license_number'),
                'status'       => 'pending',
                'is_active'    => 0,
            ];

            $driverResult = $driverModel->insert($driverData);

            if (!$driverResult) {
                // Rollback user creation
                $this->userModel->delete($userId);
                return redirect()->back()->withInput()->with('driver_error', 'Failed to submit application. Please try again.');
            }

            // Send confirmation email
            try {
                $emailService = new \App\Libraries\EmailService();
                $emailService->sendApplicationReceived(
                    $this->request->getPost('driver_email'),
                    $this->request->getPost('driver_name'),
                    'driver'
                );
            } catch (\Exception $e) {
                log_message('error', 'Failed to send application confirmation email: ' . $e->getMessage());
            }

            return redirect()->back()->with('driver_success', 'Thank you for applying to become a FoodDash driver! Our team will review your application and contact you within 2-3 business days.');

        } elseif ($partnerType === 'restaurant') {
            // Validate restaurant registration
            $rules = [
                'restaurant_name'   => 'required|min_length[3]',
                'restaurant_phone' => 'required|min_length[10]',
                'restaurant_address' => 'required',
                // latitude/longitude removed (no map)
                'owner_name'        => 'required|min_length[3]',
                'owner_email'       => 'required|valid_email',
                'owner_phone'       => 'required|min_length[10]',
                'owner_password'    => 'required|min_length[8]',
                'restaurant_terms'  => 'required',
            ];

            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('restaurant_error', $this->validator->getErrors());
            }

            // Check if email already exists
            $existingUser = $this->userModel->where('email', $this->request->getPost('owner_email'))->first();
            if ($existingUser) {
                return redirect()->back()->withInput()->with('restaurant_error', 'Email already registered');
            }

            // Create user account
            $userId = $this->userModel->insert([
                'email'      => $this->request->getPost('owner_email'),
                'password'   => $this->request->getPost('owner_password'),
                'role'       => 'restaurant',
                'is_active'  => 1, // Allow immediate login after registration
            ]);

            if (!$userId) {
                return redirect()->back()->withInput()->with('restaurant_error', 'Failed to create account. Please try again.');
            }

            // Create restaurant record
            $restaurantModel = new \App\Models\RestaurantModel();
            $restaurantData = [
                'user_id'    => $userId,
                'name'       => $this->request->getPost('restaurant_name'),
                'address'    => $this->request->getPost('restaurant_address'),
                'status'     => 'pending',
                'is_active'  => 0,
            ];

            $restaurantResult = $restaurantModel->insert($restaurantData);

            if (!$restaurantResult) {
                // Rollback user creation
                $this->userModel->delete($userId);
                return redirect()->back()->withInput()->with('restaurant_error', 'Failed to submit registration. Please try again.');
            }

            // Send confirmation email
            try {
                $emailService = new \App\Libraries\EmailService();
                $emailService->sendApplicationReceived(
                    $this->request->getPost('owner_email'),
                    $this->request->getPost('restaurant_name'),
                    'restaurant'
                );
            } catch (\Exception $e) {
                log_message('error', 'Failed to send application confirmation email: ' . $e->getMessage());
            }

            return redirect()->back()->with('restaurant_success', 'Thank you for registering your restaurant with FoodDash! Our team will review your application and contact you within 2-3 business days.');
        }

        return redirect()->to('/partner');
    }
}
