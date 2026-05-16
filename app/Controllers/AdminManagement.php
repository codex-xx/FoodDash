<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RestaurantModel;
use App\Models\DriverModel;

class AdminManagement extends BaseController
{
    protected $userModel;
    protected $restaurantModel;
    protected $driverModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->restaurantModel = new RestaurantModel();
        $this->driverModel = new DriverModel();
    }

    /**
     * Admin: View all users
     */
    public function users()
    {
        return redirect()->to('/admin/rbac');
    }

    /**
     * Admin: Suspend user account
     */
    public function suspendUser($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_staff_accounts')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
        }

        if ((int) $session->get('user_id') === (int) $id) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'You cannot suspend your own account']);
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'User not found']);
        }

        $this->userModel->update($id, ['is_active' => 0]);

        if (($user['role'] ?? null) === 'restaurant') {
            $restaurant = $this->restaurantModel->where('user_id', $id)->first();
            if ($restaurant) {
                $this->restaurantModel->update($restaurant['id'], ['is_active' => 0]);
            }
        }

        return $this->response->setJSON(['success' => true, 'message' => 'User suspended']);
    }

    /**
     * Admin: Activate user account
     */
    public function activateUser($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_staff_accounts')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'User not found']);
        }

        $this->userModel->update($id, ['is_active' => 1]);

        if (($user['role'] ?? null) === 'restaurant') {
            $restaurant = $this->restaurantModel->where('user_id', $id)->first();
            if ($restaurant) {
                $this->restaurantModel->update($restaurant['id'], [
                    'status' => 'approved',
                    'is_active' => 1,
                ]);
            }
        }

        return $this->response->setJSON(['success' => true, 'message' => 'User activated']);
    }

    /**
     * Admin: View pending restaurant registrations
     */
    public function pendingRestaurants()
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return redirect()->to('/login')->with('error', 'Unauthorized');
        }

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_restaurant_information')) {
            return view('errors/unauthorized', ['message' => 'Permission denied']);
        }

        $restaurants = $this->restaurantModel->where('status', 'pending')->findAll();

        return view('admin/restaurants/pending', ['restaurants' => $restaurants]);
    }

    /**
     * Admin: Approve restaurant
     */
    public function approveRestaurant($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_restaurant_information')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
        }

        $restaurant = $this->restaurantModel->find($id);
        if (!$restaurant) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Restaurant not found']);
        }

        $this->restaurantModel->update($id, [
            'status' => 'approved',
            'is_active' => 1,
        ]);
        if ($restaurant['user_id']) {
            $this->userModel->update($restaurant['user_id'], [
                'password'  => 'password123',
                'is_active' => 1,
            ]);
            
            // Send approval email
            $user = $this->userModel->find($restaurant['user_id']);
            if ($user && $user['email']) {
                try {
                    $emailService = new \App\Libraries\EmailService();
                    $emailService->sendApplicationApproved($user['email'], $restaurant['name'], 'restaurant', 'password123');
                } catch (\Exception $e) {
                    log_message('error', 'Failed to send restaurant approval email: ' . $e->getMessage());
                }
            }
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Restaurant approved']);
    }

    /**
     * Admin: Reject restaurant
     */
    public function rejectRestaurant($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_restaurant_information')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
        }

        $restaurant = $this->restaurantModel->find($id);
        if (!$restaurant) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Restaurant not found']);
        }

        $this->restaurantModel->update($id, [
            'status' => 'rejected',
            'is_active' => 0,
        ]);
        if ($restaurant['user_id']) {
            $this->userModel->update($restaurant['user_id'], ['is_active' => 0]);
            
            // Send rejection email
            $user = $this->userModel->find($restaurant['user_id']);
            if ($user && $user['email']) {
                try {
                    $emailService = new \App\Libraries\EmailService();
                    $emailService->sendApplicationRejected($user['email'], $restaurant['name'], 'restaurant');
                } catch (\Exception $e) {
                    log_message('error', 'Failed to send restaurant rejection email: ' . $e->getMessage());
                }
            }
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Restaurant rejected']);
    }

    /**
     * Admin: View pending driver registrations
     */
    public function pendingDrivers()
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return redirect()->to('/login')->with('error', 'Unauthorized');
        }

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_drivers')) {
            return view('errors/unauthorized', ['message' => 'Permission denied']);
        }

        $drivers = (new DriverModel())
            ->where('status', 'pending')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $approvedDrivers = (new DriverModel())
            ->where('status', 'approved')
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        return view('admin/drivers/pending', [
            'drivers' => $drivers,
            'approvedDrivers' => $approvedDrivers,
        ]);
    }

    /**
     * Admin: Approve driver
     */
    public function approveDriver($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_drivers')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
        }

        $driver = $this->driverModel->find($id);
        if (!$driver) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Driver not found']);
        }

        $this->driverModel->update($id, [
            'status' => 'approved',
            'is_active' => 1,
        ]);
        if ($driver['user_id']) {
            $this->userModel->update($driver['user_id'], [
                'is_active' => 1,
            ]);
        }
        
        // Send approval email directly to driver's email address
        if ($driver['email']) {
            try {
                $emailService = new \App\Libraries\EmailService();
                $emailService->sendApplicationApproved($driver['email'], $driver['name'], 'driver');
            } catch (\Exception $e) {
                log_message('error', 'Failed to send driver approval email: ' . $e->getMessage());
            }
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Driver approved']);
    }

    /**
     * Admin: Reject driver
     */
    public function rejectDriver($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_drivers')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
        }

        $driver = $this->driverModel->find($id);
        if (!$driver) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Driver not found']);
        }

        $this->driverModel->update($id, [
            'status' => 'rejected',
            'is_active' => 0,
        ]);
        if ($driver['user_id']) {
            $this->userModel->update($driver['user_id'], ['is_active' => 0]);
        }
        
        // Send rejection email directly to driver's email address
        if ($driver['email']) {
            try {
                $emailService = new \App\Libraries\EmailService();
                $emailService->sendApplicationRejected($driver['email'], $driver['name'], 'driver');
            } catch (\Exception $e) {
                log_message('error', 'Failed to send driver rejection email: ' . $e->getMessage());
            }
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Driver rejected']);
    }

    /**
     * Get revenue summary data (table format only)
     */
    public function getRevenueSummary()
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('view_sales_reports') && !$permissions->hasPermission('access_analytics')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
        }

        $db = \Config\Database::connect();

        // Revenue by restaurant (last 30 days)
        $revenueByRestaurant = $db->query("
            SELECT r.name, COUNT(o.id) as orders, IFNULL(SUM(o.total_amount), 0) as revenue
            FROM orders o
            JOIN restaurants r ON r.id = o.restaurant_id
            WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND o.status = 'delivered'
            GROUP BY o.restaurant_id
            ORDER BY revenue DESC
        ")->getResultArray();

        return $this->response->setJSON([
            'revenueByRestaurant' => $revenueByRestaurant,
        ]);
    }
}
