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
        // Try JSON first
        $json = $this->request->getJSON(true);
        if ($json && isset($json[$key])) {
            return $json[$key];
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
                    'user'  => $customer,
                    'token' => $apiToken
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
                    'user'  => $customer,
                    'token' => $apiToken
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
                'phone'          => 'required|max_length[20]',
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
                'phone'          => $this->getInput('phone'),
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
                    'user'  => $driver,
                    'token' => $apiToken
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
                    'user'  => $driver,
                    'token' => $apiToken
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
}
