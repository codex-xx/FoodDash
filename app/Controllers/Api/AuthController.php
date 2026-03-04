<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CustomerModel;
use App\Models\DriverModel;

class AuthController extends ResourceController
{
    protected $format = 'json';

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
            ];

            if (!$this->validate($rules)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $this->validator->getErrors()
                ], 400);
            }

            $customerModel = new CustomerModel();
            $apiToken = $customerModel->generateApiToken();

            $data = [
                'name'      => $this->getInput('name'),
                'email'     => $this->getInput('email'),
                'password'  => $this->getInput('password'),
                'phone'     => $this->getInput('phone') ?? '',
                'api_token' => $apiToken,
                'is_active' => 1,
            ];

            $customerId = $customerModel->insert($data);

            if (!$customerId) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Registration failed'
                ], 500);
            }

            $customer = $customerModel->find($customerId);
            unset($customer['password']);

            return $this->respond([
                'success' => true,
                'message' => 'Registration successful',
                'data'    => [
                    'user'      => $customer,
                    'token'     => $apiToken,
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
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ], 401);
            }

            if (!(int) $customer['is_active']) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Account is disabled'
                ], 401);
            }

            if (!password_verify($password, $customer['password'])) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ], 401);
            }

            // Generate new token on login
            $apiToken = $customerModel->generateApiToken();
            $customerModel->update($customer['id'], ['api_token' => $apiToken]);

            // Update FCM token if provided
            $fcmToken = $this->getInput('fcm_token');
            if ($fcmToken) {
                $customerModel->update($customer['id'], ['fcm_token' => $fcmToken]);
            }

            $customer['api_token'] = $apiToken;
            unset($customer['password']);

            return $this->respond([
                'success' => true,
                'message' => 'Login successful',
                'data'    => [
                    'user'      => $customer,
                    'token'     => $apiToken,
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
                'vehicle_number' => 'permit_empty|max_length[50]',
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
            $apiToken = bin2hex(random_bytes(32));

            $data = [
                'name'           => $this->getInput('name'),
                'email'          => $this->getInput('email'),
                'password'       => password_hash($this->getInput('password'), PASSWORD_DEFAULT),
                'phone'          => $this->getInput('phone') ?? '',
                'vehicle_type'   => $this->getInput('vehicle_type') ?? '',
                'vehicle_number' => $this->getInput('vehicle_number') ?? '',
                'license_number' => $this->getInput('license_number') ?? '',
                'api_token'      => $apiToken,
                'status'         => 'pending', // Needs admin approval
                'is_active'      => 0,
            ];

            $driverId = $driverModel->insert($data);

            if (!$driverId) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Registration failed'
                ], 500);
            }

            $driver = $driverModel->find($driverId);
            unset($driver['password']);

            return $this->respond([
                'success' => true,
                'message' => 'Registration successful. Please wait for admin approval.',
                'data'    => [
                    'user'      => $driver,
                    'token'     => $apiToken,
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
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ], 401);
            }

            if (!(int) $driver['is_active']) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Account is not approved yet or disabled'
                ], 401);
            }

            if (!password_verify($password, $driver['password'])) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ], 401);
            }

            // Generate new token on login
            $apiToken = bin2hex(random_bytes(32));
            $driverModel->update($driver['id'], ['api_token' => $apiToken]);

            // Update FCM token if provided
            $fcmToken = $this->getInput('fcm_token');
            if ($fcmToken) {
                $driverModel->update($driver['id'], ['fcm_token' => $fcmToken]);
            }

            $driver['api_token'] = $apiToken;
            unset($driver['password']);

            return $this->respond([
                'success' => true,
                'message' => 'Login successful',
                'data'    => [
                    'user'      => $driver,
                    'token'     => $apiToken,
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
        $authHeader = $this->request->getHeaderLine('Authorization');
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        // Try to find and invalidate customer token
        $customerModel = new CustomerModel();
        $customer = $customerModel->where('api_token', $token)->first();
        if ($customer) {
            $customerModel->update($customer['id'], ['api_token' => null]);
            return $this->respond([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        }

        // Try to find and invalidate driver token
        $driverModel = new DriverModel();
        $driver = $driverModel->where('api_token', $token)->first();
        if ($driver) {
            $driverModel->update($driver['id'], ['api_token' => null]);
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
