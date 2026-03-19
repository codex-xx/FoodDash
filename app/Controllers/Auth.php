<?php

namespace App\Controllers;

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
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]'
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $user = $this->userModel->where('email', $email)->first();

        if (! $user) {
            return redirect()->back()->withInput()->with('error', 'Email not found');
        }

        if (! (int) $user['is_active']) {
            return redirect()->back()->withInput()->with('error', 'Account is disabled');
        }

        if (! password_verify($password, $user['password'])) {
            return redirect()->back()->withInput()->with('error', 'Incorrect email or password');
        }

        // Successful login: create session
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

        // Role-based redirect
        if ($user['role'] === 'admin') {
            return redirect()->to('/dashboard/admin');
        }

        return redirect()->to('/dashboard/restaurant');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', 'Logged out');
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

        $newHash = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);

        $this->userModel->update($user['id'], [
            'password'      => $newHash,
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
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

            // Create user account
            $userId = $this->userModel->insert([
                'email'      => $this->request->getPost('driver_email'),
                'password'   => $hashedPassword,
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
                'vehicle_number' => $this->request->getPost('license_plate'),
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
                'owner_name'        => 'required|min_length[3]',
                'owner_email'       => 'required|valid_email',
                'owner_phone'       => 'required|min_length[10]',
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

            // Generate a random password (they can reset it later)
            $tempPassword = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

            // Create user account
            $userId = $this->userModel->insert([
                'email'      => $this->request->getPost('owner_email'),
                'password'   => $hashedPassword,
                'role'       => 'restaurant',
                'is_active'  => 0, // Not active until approved
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
