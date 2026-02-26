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
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

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

        // Try to send email if Mail is configured, otherwise show link in flash for dev/testing
        try {
            $emailService = service('email');
            $emailService->setTo($user['email']);
            $emailService->setSubject('Password reset for FoodDash');
            $emailService->setMessage("Use the following link to reset your password: {$resetLink}");
            $emailService->send();
            $message = 'Password reset link sent to your email.';
        } catch (\Exception $e) {
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
}
