<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CustomerModel;
use App\Models\DriverModel;
use App\Models\AuthTokenModel;
use App\Models\LoginActivityModel;
use App\Libraries\JwtService;
use App\Libraries\EmailService;
use App\Libraries\SensitiveDataService;

class AuthController extends ResourceController
{
    protected $format = 'json';
    protected array $schemaSupportCache = [];

    /**
     * Read the first non-empty value from a list of possible input keys.
     */
    protected function getFirstInput(array $keys, $default = null)
    {
        foreach ($keys as $key) {
            $value = $this->getInput($key);
            if ($value !== null && trim((string) $value) !== '') {
                return $value;
            }
        }

        return $default;
    }

    protected function resolvePhoneInput(): string
    {
        return (string) ($this->getFirstInput(['phone', 'phone_number', 'mobile', 'contact_number'], '') ?? '');
    }

    protected function resolveAddressInput(): string
    {
        return (string) ($this->getFirstInput(['address', 'full_address', 'location'], '') ?? '');
    }

    protected function stripDriverLocationFields(array $driver): array
    {
        unset($driver['current_latitude'], $driver['current_longitude']);

        return $driver;
    }

    protected function issueAccessToken(string $userType, array $user): string
    {
        $jwtService = new JwtService();

        $token = $jwtService->createAccessToken(
            $userType,
            (int) $user['id'],
            (string) $user['email'],
            (int) ($user['token_version'] ?? 0)
        );

        $this->persistAuthToken($userType, (int) $user['id'], $token);

        return $token;
    }

    protected function persistAuthToken(string $userType, int $userId, string $token): void
    {
        if (! $this->tableExists('auth_tokens')) {
            return;
        }

        $jwtService = new JwtService();
        $claims = $jwtService->decodeAccessToken($token);
        if (! is_array($claims)) {
            return;
        }

        $issuedAt = isset($claims['iat']) ? date('Y-m-d H:i:s', (int) $claims['iat']) : date('Y-m-d H:i:s');
        $expiresAt = isset($claims['exp']) ? date('Y-m-d H:i:s', (int) $claims['exp']) : date('Y-m-d H:i:s', strtotime('+1 hour'));

        try {
            $model = new AuthTokenModel();
            $model->insert([
                'user_type' => $userType,
                'user_id' => $userId,
                'jti' => (string) ($claims['jti'] ?? bin2hex(random_bytes(16))),
                'token_hash' => hash('sha256', $token),
                'issued_at' => $issuedAt,
                'expires_at' => $expiresAt,
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => substr((string) $this->request->getUserAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Unable to persist auth token: ' . $e->getMessage());
        }
    }

    protected function revokeCurrentToken(): void
    {
        if (! $this->tableExists('auth_tokens')) {
            return;
        }

        $claims = $this->request->tokenClaims ?? null;
        $jti = is_array($claims) ? ($claims['jti'] ?? null) : null;
        if (! is_string($jti) || trim($jti) === '') {
            return;
        }

        try {
            $model = new AuthTokenModel();
            $model->where('jti', $jti)->set([
                'revoked_at' => date('Y-m-d H:i:s'),
            ])->update();
        } catch (\Throwable $e) {
            log_message('error', 'Unable to revoke auth token: ' . $e->getMessage());
        }
    }

    protected function logLoginAttempt(?string $userType, ?int $userId, ?string $email, bool $success, ?string $reason = null): void
    {
        if (! $this->tableExists('login_activities')) {
            return;
        }

        $sensitive = new SensitiveDataService();

        try {
            $model = new LoginActivityModel();
            $model->insert([
                'user_type' => $userType,
                'user_id' => $userId,
                'email_hash' => $sensitive->hashForLookup($email),
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => substr((string) $this->request->getUserAgent(), 0, 255),
                'success' => $success ? 1 : 0,
                'failure_reason' => $success ? null : ($reason ?? 'login_failed'),
                'login_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Unable to write login activity: ' . $e->getMessage());
        }
    }

    protected function sendLoginOtp(string $email, string $name, string $otpCode): bool
    {
        try {
            $service = new EmailService();
            return $service->sendLoginOtp($email, $name !== '' ? $name : 'User', $otpCode);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to send login OTP email: ' . $e->getMessage());
            return false;
        }
    }

    protected function beginMfaChallenge(string $userType, array $user, $model)
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $model->update((int) $user['id'], [
            'login_otp_code' => $otp,
            'login_otp_expires' => $otpExpires,
        ]);

        $this->sendLoginOtp((string) $user['email'], (string) ($user['name'] ?? ''), $otp);

        return $this->respond([
            'success' => true,
            'message' => 'Verification code sent. Please complete MFA to finish login.',
            'data' => [
                'mfa_required' => true,
                'user_type' => $userType,
                'email' => $user['email'],
                'otp_expires_in' => 600,
            ],
        ]);
    }

    protected function tableHasColumn(string $table, string $column): bool
    {
        $key = $table . ':' . $column;
        if (array_key_exists($key, $this->schemaSupportCache)) {
            return $this->schemaSupportCache[$key];
        }

        try {
            $hasColumn = db_connect()->fieldExists($column, $table);
        } catch (\Throwable $e) {
            $hasColumn = false;
        }

        $this->schemaSupportCache[$key] = $hasColumn;
        return $hasColumn;
    }

    protected function tableExists(string $table): bool
    {
        $key = 'table:' . $table;
        if (array_key_exists($key, $this->schemaSupportCache)) {
            return (bool) $this->schemaSupportCache[$key];
        }

        try {
            $exists = db_connect()->tableExists($table);
        } catch (\Throwable $e) {
            $exists = false;
        }

        $this->schemaSupportCache[$key] = $exists;
        return $exists;
    }

    protected function supportsMfaColumns(string $table): bool
    {
        return $this->tableHasColumn($table, 'mfa_enabled')
            && $this->tableHasColumn($table, 'login_otp_code')
            && $this->tableHasColumn($table, 'login_otp_expires');
    }

    protected function supportsTokenVersion(string $table): bool
    {
        return $this->tableHasColumn($table, 'token_version');
    }

    protected function isMfaEnforced(): bool
    {
        $raw = env('auth.enforceMfa');
        if ($raw === null) {
            return false;
        }

        $value = strtolower(trim((string) $raw));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    public function __construct()
    {
        // Set CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Content-Type: application/json');
    }

    /**
     * Helper to get input from both JSON and form-data
     */
    protected function getInput(string $key)
    {
        // Try JSON first (safely)
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json) && array_key_exists($key, $json)) {
                return $json[$key];
            }
        } catch (\Throwable $e) {
            // Not JSON payload or malformed JSON - continue with POST/getVar.
        }
        
        // Try POST
        $post = $this->request->getPost($key);
        if ($post !== null) {
            return $post;
        }
        
        // Try getVar (works for both)
        return $this->request->getVar($key);
    }

    /**
     * Normalize incoming user type values.
     */
    protected function normalizeUserType($userType): ?string
    {
        if ($userType === null) {
            return null;
        }

        if (!is_scalar($userType)) {
            return null;
        }

        $normalized = strtolower(trim((string) $userType));

        if (in_array($normalized, ['customer', 'user', 'client'], true)) {
            return 'customer';
        }

        if (in_array($normalized, ['driver', 'rider', 'delivery'], true)) {
            return 'driver';
        }

        return null;
    }

    /**
     * Resolve role for register endpoint.
     */
    protected function resolveRegisterUserType(): ?string
    {
        $rawType = $this->getInput('user_type')
            ?? $this->getInput('role')
            ?? $this->getInput('type');

        $requestedType = $this->normalizeUserType(
            $rawType
        );

        if ($rawType !== null && trim((string) $rawType) !== '' && $requestedType === null) {
            return null;
        }

        if ($requestedType !== null) {
            return $requestedType;
        }

        $hasDriverFields =
            !empty($this->getInput('vehicle_type'))
            || !empty($this->getInput('vehicle_number'))
            || !empty($this->getInput('license_number'));

        return $hasDriverFields ? 'driver' : 'customer';
    }

    /**
     * Resolve role for login endpoint.
     */
    protected function resolveLoginUserType(): ?string
    {
        $rawType = $this->getInput('user_type')
            ?? $this->getInput('role')
            ?? $this->getInput('type');

        $requestedType = $this->normalizeUserType(
            $rawType
        );

        if ($rawType !== null && trim((string) $rawType) !== '' && $requestedType === null) {
            return null;
        }

        if ($requestedType !== null) {
            return $requestedType;
        }

        $email = $this->getInput('email');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'customer';
        }

        $customerExists = (new CustomerModel())
            ->select('id')
            ->where('email', $email)
            ->first() !== null;

        $driverExists = (new DriverModel())
            ->select('id')
            ->where('email', $email)
            ->first() !== null;

        if ($driverExists && !$customerExists) {
            return 'driver';
        }

        if ($customerExists && !$driverExists) {
            return 'customer';
        }

        if ($customerExists && $driverExists) {
            return null;
        }

        return 'customer';
    }

    /**
     * Role-aware Registration (customer or driver)
     * POST /api/register
     */
    public function register()
    {
        $userType = $this->resolveRegisterUserType();

        if ($userType === null) {
            return $this->respond([
                'success' => false,
                'message' => 'Invalid user_type. Use customer or driver.'
            ], 400);
        }

        if ($userType === 'driver') {
            return $this->driverRegister();
        }

        return $this->customerRegister();
    }

    /**
     * Role-aware Login (customer or driver)
     * POST /api/login
     */
    public function login()
    {
        $userType = $this->resolveLoginUserType();

        if ($userType === null) {
            return $this->respond([
                'success' => false,
                'message' => 'Please pass a valid user_type (customer or driver). If this email exists in both tables, user_type is required.'
            ], 400);
        }

        if ($userType === 'driver') {
            return $this->driverLogin();
        }

        return $this->customerLogin();
    }

    /**
     * Customer Registration
     * POST /api/register or /api/customer/register
     */
    public function customerRegister()
    {
        try {
            $rules = [
                'name'     => 'required|min_length[2]|max_length[255]',
                'email'    => 'required|valid_email|is_unique[customers.email]',
                'password' => 'required|min_length[6]',
                'phone'    => 'permit_empty|max_length[20]',
                'address'  => 'permit_empty|max_length[500]',
            ];

            if (!$this->validate($rules)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $this->validator->getErrors()
                ], 400);
            }

            $customerModel = new CustomerModel();

            $data = [
                'name'      => $this->getInput('name'),
                'email'     => $this->getInput('email'),
                'password'  => $this->getInput('password'),
                'phone'     => $this->resolvePhoneInput(),
                'address'   => $this->resolveAddressInput(),
                'is_active' => 1,
            ];

            if ($this->supportsMfaColumns('customers')) {
                $data['mfa_enabled'] = $this->isMfaEnforced() ? 1 : 0;
            }

            $customerId = $customerModel->insert($data);

            if (!$customerId) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Registration failed'
                ], 500);
            }

            $customer = $customerModel->find($customerId);
            $token = $this->issueAccessToken('customer', $customer);
            unset($customer['password']);

            return $this->respond([
                'success' => true,
                'message' => 'Registration successful',
                'data'    => [
                    'user'      => $customer,
                    'token'     => $token,
                    'user_type' => 'customer'
                ]
            ], 201);
            
        } catch (\Exception $e) {
            log_message('error', 'Registration error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Customer Login
     * POST /api/login or /api/customer/login
     */
    public function customerLogin()
    {
        try {
            $rules = [
                'email'    => 'required|valid_email',
                'password' => 'required',
            ];

            if (!$this->validate($rules)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $this->validator->getErrors()
                ], 400);
            }

            $email = $this->getInput('email');
            $password = $this->getInput('password');

            $customerModel = new CustomerModel();
            $customer = $customerModel->where('email', $email)->first();

            if (!$customer) {
                $this->logLoginAttempt('customer', null, (string) $email, false, 'user_not_found');
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ], 401);
            }

            if (!(int) $customer['is_active']) {
                $this->logLoginAttempt('customer', (int) $customer['id'], (string) $email, false, 'account_disabled');
                return $this->respond([
                    'success' => false,
                    'message' => 'Account is disabled'
                ], 401);
            }

            if (!password_verify($password, $customer['password'])) {
                $this->logLoginAttempt('customer', (int) $customer['id'], (string) $email, false, 'invalid_password');
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ], 401);
            }

            if (password_needs_rehash($customer['password'], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT)) {
                $customerModel->update((int) $customer['id'], ['password' => $password]);
                $customer = $customerModel->find((int) $customer['id']) ?? $customer;
            }

            if ($this->supportsMfaColumns('customers') && $this->isMfaEnforced() && (int) ($customer['mfa_enabled'] ?? 0) === 1) {
                return $this->beginMfaChallenge('customer', $customer, $customerModel);
            }

            // Update FCM token if provided
            $fcmToken = $this->getInput('fcm_token');
            if ($fcmToken) {
                $customerModel->update($customer['id'], ['fcm_token' => $fcmToken]);
            }

            $customer = $customerModel->find((int) $customer['id']) ?? $customer;
            $token = $this->issueAccessToken('customer', $customer);
            $this->logLoginAttempt('customer', (int) $customer['id'], (string) $email, true, null);
            unset($customer['password']);

            return $this->respond([
                'success' => true,
                'message' => 'Login successful',
                'data'    => [
                    'user'      => $customer,
                    'token'     => $token,
                    'user_type' => 'customer'
                ]
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Login error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Driver Registration
     * POST /api/driver/register
     */
    public function driverRegister()
    {
        try {
            $rules = [
                'name'           => 'required|min_length[2]|max_length[255]',
                'email'          => 'required|valid_email|is_unique[drivers.email]',
                'password'       => 'required|min_length[6]',
                'phone'          => 'permit_empty|max_length[20]',
                'vehicle_type'   => 'permit_empty|max_length[50]',
                'license_number' => 'permit_empty|max_length[50]',
            ];

            if (!$this->validate($rules)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $this->validator->getErrors()
                ], 400);
            }

            $driverModel = new DriverModel();

            $data = [
                'name'           => $this->getInput('name'),
                'email'          => $this->getInput('email'),
                'password'       => (string) $this->getInput('password'),
                'phone'          => $this->resolvePhoneInput(),
                'vehicle_type'   => $this->getInput('vehicle_type') ?? '',
                'license_number' => $this->getInput('license_number') ?? '',
                'status'         => 'pending', // Needs admin approval
                'is_active'      => 0,
            ];

            if ($this->supportsMfaColumns('drivers')) {
                $data['mfa_enabled'] = $this->isMfaEnforced() ? 1 : 0;
            }

            $driverId = $driverModel->insert($data);

            if (!$driverId) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Registration failed'
                ], 500);
            }

            $driver = $driverModel->find($driverId);
            unset($driver['password']);
            $driver = $this->stripDriverLocationFields($driver);

            // Send application received confirmation email
            try {
                $emailService = new \App\Libraries\EmailService();
                $emailService->sendApplicationReceived($driver['email'], $driver['name'], 'driver');
            } catch (\Exception $e) {
                log_message('error', 'Failed to send driver application confirmation email: ' . $e->getMessage());
            }

            return $this->respond([
                'success' => true,
                'message' => 'Registration successful. Please wait for admin approval.',
                'data'    => [
                    'user'      => $driver,
                    'token'     => null,
                    'user_type' => 'driver'
                ]
            ], 201);
            
        } catch (\Exception $e) {
            log_message('error', 'Driver registration error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Driver Login
     * POST /api/driver/login
     */
    public function driverLogin()
    {
        try {
            $rules = [
                'email'    => 'required|valid_email',
                'password' => 'required',
            ];

            if (!$this->validate($rules)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $this->validator->getErrors()
                ], 400);
            }

            $email = $this->getInput('email');
            $password = $this->getInput('password');

            $driverModel = new DriverModel();
            $driver = $driverModel->where('email', $email)->first();

            if (!$driver) {
                $this->logLoginAttempt('driver', null, (string) $email, false, 'user_not_found');
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ], 401);
            }

            if (!(int) $driver['is_active']) {
                $this->logLoginAttempt('driver', (int) $driver['id'], (string) $email, false, 'account_disabled');
                return $this->respond([
                    'success' => false,
                    'message' => 'Account is not approved yet or disabled'
                ], 401);
            }

            if (!password_verify($password, $driver['password'])) {
                $this->logLoginAttempt('driver', (int) $driver['id'], (string) $email, false, 'invalid_password');
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ], 401);
            }

            if (password_needs_rehash($driver['password'], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT)) {
                $driverModel->update((int) $driver['id'], ['password' => $password]);
                $driver = $driverModel->find((int) $driver['id']) ?? $driver;
            }

            if ($this->supportsMfaColumns('drivers') && $this->isMfaEnforced() && (int) ($driver['mfa_enabled'] ?? 0) === 1) {
                return $this->beginMfaChallenge('driver', $driver, $driverModel);
            }

            // Update FCM token if provided
            $fcmToken = $this->getInput('fcm_token');
            if ($fcmToken) {
                $driverModel->update($driver['id'], ['fcm_token' => $fcmToken]);
            }

            $driver = $driverModel->find((int) $driver['id']) ?? $driver;
            $token = $this->issueAccessToken('driver', $driver);
            $this->logLoginAttempt('driver', (int) $driver['id'], (string) $email, true, null);
            unset($driver['password']);
            $driver = $this->stripDriverLocationFields($driver);

            return $this->respond([
                'success' => true,
                'message' => 'Login successful',
                'data'    => [
                    'user'      => $driver,
                    'token'     => $token,
                    'user_type' => 'driver'
                ]
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Driver login error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout (invalidate token)
     * POST /api/logout
     */
    public function logout()
    {
        $userType = $this->request->userType ?? null;

        if ($userType === 'customer' && isset($this->request->customer['id'])) {
            $customerModel = new CustomerModel();
            $customer = $this->request->customer;
            $updateData = ['api_token' => null];
            if ($this->supportsTokenVersion('customers')) {
                $updateData['token_version'] = (int) ($customer['token_version'] ?? 0) + 1;
            }
            $customerModel->update((int) $customer['id'], $updateData);
            $this->revokeCurrentToken();

            return $this->respond([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        }

        if ($userType === 'driver' && isset($this->request->driver['id'])) {
            $driverModel = new DriverModel();
            $driver = $this->request->driver;
            $updateData = ['api_token' => null];
            if ($this->supportsTokenVersion('drivers')) {
                $updateData['token_version'] = (int) ($driver['token_version'] ?? 0) + 1;
            }
            $driverModel->update((int) $driver['id'], $updateData);
            $this->revokeCurrentToken();

            return $this->respond([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        }

        return $this->respond([
            'success' => false,
            'message' => 'Invalid token'
        ], 401);
    }

    /**
     * Verify Login MFA OTP and issue JWT
     * POST /api/verify-login-otp
     */
    public function verifyLoginOtp()
    {
        try {
            $email = trim((string) $this->getInput('email'));
            $otp = (string) ($this->getInput('otp') ?? $this->getInput('code') ?? $this->getInput('verification_code'));
            $userType = $this->normalizeUserType($this->getInput('user_type') ?? $this->getInput('role') ?? 'customer');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->respond(['success' => false, 'message' => 'Valid email is required'], 400);
            }

            if (!preg_match('/^\d{6}$/', $otp)) {
                return $this->respond(['success' => false, 'message' => 'A valid 6-digit OTP is required'], 400);
            }

            if (!in_array($userType, ['customer', 'driver'], true)) {
                return $this->respond(['success' => false, 'message' => 'Valid user_type is required'], 400);
            }

            if ($userType === 'customer') {
                if (!$this->supportsMfaColumns('customers')) {
                    return $this->respond(['success' => false, 'message' => 'MFA verification is not enabled on this server yet.'], 400);
                }

                $customerModel = new CustomerModel();
                $customer = $customerModel
                    ->where('email', $email)
                    ->where('login_otp_code', $otp)
                    ->where('login_otp_expires >=', date('Y-m-d H:i:s'))
                    ->first();

                if (!$customer || !(int) ($customer['is_active'] ?? 0)) {
                    return $this->respond(['success' => false, 'message' => 'Invalid or expired login verification code'], 400);
                }

                $customerModel->update((int) $customer['id'], [
                    'login_otp_code' => null,
                    'login_otp_expires' => null,
                ]);

                $customer = $customerModel->find((int) $customer['id']) ?? $customer;
                $token = $this->issueAccessToken('customer', $customer);
                unset($customer['password']);

                return $this->respond([
                    'success' => true,
                    'message' => 'Login verified successfully',
                    'data' => [
                        'user' => $customer,
                        'token' => $token,
                        'user_type' => 'customer',
                    ],
                ]);
            }

            if (!$this->supportsMfaColumns('drivers')) {
                return $this->respond(['success' => false, 'message' => 'MFA verification is not enabled on this server yet.'], 400);
            }

            $driverModel = new DriverModel();
            $driver = $driverModel
                ->where('email', $email)
                ->where('login_otp_code', $otp)
                ->where('login_otp_expires >=', date('Y-m-d H:i:s'))
                ->first();

            if (!$driver || !(int) ($driver['is_active'] ?? 0)) {
                return $this->respond(['success' => false, 'message' => 'Invalid or expired login verification code'], 400);
            }

            $driverModel->update((int) $driver['id'], [
                'login_otp_code' => null,
                'login_otp_expires' => null,
            ]);

            $driver = $driverModel->find((int) $driver['id']) ?? $driver;
            $token = $this->issueAccessToken('driver', $driver);
            unset($driver['password']);
            $driver = $this->stripDriverLocationFields($driver);

            return $this->respond([
                'success' => true,
                'message' => 'Login verified successfully',
                'data' => [
                    'user' => $driver,
                    'token' => $token,
                    'user_type' => 'driver',
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Verify login OTP error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }

    /**
     * Forgot Password - Send reset code via email
     * POST /api/forgot-password
     */
    public function forgotPassword()
    {
        try {
            $rules = [
                'email' => 'required|valid_email',
            ];

            if (!$this->validate($rules)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $this->validator->getErrors()
                ], 400);
            }

            $email = $this->getInput('email');

            $customerModel = new CustomerModel();
            $customer = $customerModel->where('email', $email)->first();

            if (!$customer) {
                // Don't reveal if email exists for security
                return $this->respond([
                    'success' => true,
                    'message' => 'If an account with that email exists, a reset code has been sent.'
                ]);
            }

            // Generate 6-digit reset code
            $resetCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $resetToken = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Update customer with reset info
            $customerModel->update($customer['id'], [
                'reset_code'    => $resetCode,
                'reset_token'   => $resetToken,
                'reset_expires' => $expires,
            ]);

            // Send email using PHPMailer
            $emailService = new \App\Libraries\EmailService();
            $sent = $emailService->sendPasswordResetCode(
                $customer['email'],
                $customer['name'],
                $resetCode
            );

            if (!$sent) {
                log_message('error', 'Failed to send reset email to: ' . $email);
                return $this->respond([
                    'success' => false,
                    'message' => 'Failed to send reset email. Please try again later.'
                ], 500);
            }

            return $this->respond([
                'success' => true,
                'message' => 'Password reset code sent to your email.',
                'data'    => [
                    'reset_token' => $resetToken // Used to verify the code
                ]
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Forgot password error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }

    /**
     * Verify Reset Code
     * POST /api/verify-reset-code or /api/verify-code
     */
    public function verifyResetCode()
    {
        try {
            $email = $this->getInput('email');
            $code = $this->getInput('code') ?? $this->getInput('otp') ?? $this->getInput('verification_code');

            log_message('info', 'Verify code request - Email: ' . $email . ', Code: ' . $code);

            // Manual validation
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Valid email is required'
                ], 400);
            }

            if (empty($code) || strlen($code) != 6) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Enter 6-digit code'
                ], 400);
            }

            $customerModel = new CustomerModel();
            $customer = $customerModel->where('email', $email)
                ->where('reset_code', $code)
                ->where('reset_expires >=', date('Y-m-d H:i:s'))
                ->first();

            if (!$customer) {
                log_message('info', 'Verify code failed - Invalid code for email: ' . $email);
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid or expired reset code.'
                ], 400);
            }

            log_message('info', 'Code verified successfully for: ' . $email);

            return $this->respond([
                'success' => true,
                'message' => 'Code verified successfully.',
                'data'    => [
                    'reset_token' => $customer['reset_token'],
                    'email' => $email
                ]
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Verify reset code error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }

    /**
     * Reset Password with new password
     * POST /api/reset-password
     */
    public function resetPassword()
    {
        try {
            // Get password from multiple possible field names
            $password = $this->getInput('password') 
                ?? $this->getInput('new_password') 
                ?? $this->getInput('newPassword');

            $email = $this->getInput('email');
            $resetToken = $this->getInput('reset_token') ?? $this->getInput('resetToken');

            // Log what we received for debugging
            log_message('info', 'Reset password request - Email: ' . $email . ', Token: ' . ($resetToken ? 'present' : 'missing') . ', Password length: ' . strlen($password ?? ''));

            // Manual validation
            $errors = [];
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Valid email is required';
            }
            if (empty($resetToken)) {
                $errors['reset_token'] = 'Reset token is required';
            }
            if (empty($password) || strlen($password) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }

            if (!empty($errors)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $errors
                ], 400);
            }

            $customerModel = new CustomerModel();
            $customer = $customerModel->where('email', $email)
                ->where('reset_token', $resetToken)
                ->where('reset_expires >=', date('Y-m-d H:i:s'))
                ->first();

            if (!$customer) {
                log_message('info', 'Reset password failed - No matching customer for email: ' . $email);
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid or expired reset token.'
                ], 400);
            }

            // Update password and clear reset fields
            $customerModel->update($customer['id'], [
                'password'      => $password, // Will be hashed by model
                'reset_token'   => null,
                'reset_expires' => null,
                'reset_code'    => null,
            ]);

            log_message('info', 'Password reset successful for: ' . $email);

            return $this->respond([
                'success' => true,
                'message' => 'Password reset successfully. You can now login with your new password.'
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Reset password error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }
}
