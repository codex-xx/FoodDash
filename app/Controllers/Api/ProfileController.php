<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CustomerModel;
use App\Models\DriverModel;

class ProfileController extends ResourceController
{
    protected $format = 'json';

    /**
     * Get current user profile
     * GET /api/profile
     */
    public function index()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        // Check customer
        $customerModel = new CustomerModel();
        $customer = $customerModel->where('api_token', $token)->first();

        if ($customer) {
            unset($customer['password']);
            return $this->respond([
                'success'   => true,
                'user_type' => 'customer',
                'data'      => $customer
            ]);
        }

        // Check driver
        $driverModel = new DriverModel();
        $driver = $driverModel->where('api_token', $token)->first();

        if ($driver) {
            unset($driver['password']);
            unset($driver['current_latitude'], $driver['current_longitude']);
            return $this->respond([
                'success'   => true,
                'user_type' => 'driver',
                'data'      => $driver
            ]);
        }

        return $this->respond([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    /**
     * Update customer profile
     * PUT /api/profile
     */
    public function update()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        // Check customer
        $customerModel = new CustomerModel();
        $customer = $customerModel->where('api_token', $token)->first();

        if ($customer) {
            $data = [];
            
            $json = $this->request->getJSON(true);
            if (!$json) {
                $json = $this->request->getPost();
            }

            if (isset($json['name'])) {
                $data['name'] = $json['name'];
            }
            if (isset($json['phone'])) {
                $data['phone'] = $json['phone'];
            }
            if (isset($json['address'])) {
                $data['address'] = $json['address'];
            }

            if (!empty($data)) {
                $customerModel->update($customer['id'], $data);
            }

            $updatedCustomer = $customerModel->find($customer['id']);
            unset($updatedCustomer['password']);

            return $this->respond([
                'success' => true,
                'message' => 'Profile updated',
                'data'    => $updatedCustomer
            ]);
        }

        // Check driver
        $driverModel = new DriverModel();
        $driver = $driverModel->where('api_token', $token)->first();

        if ($driver) {
            $data = [];
            
            $json = $this->request->getJSON(true);
            if (!$json) {
                $json = $this->request->getPost();
            }

            if (isset($json['name'])) {
                $data['name'] = $json['name'];
            }
            if (isset($json['phone'])) {
                $data['phone'] = $json['phone'];
            }
            if (isset($json['vehicle_type'])) {
                $data['vehicle_type'] = $json['vehicle_type'];
            }

            if (!empty($data)) {
                $driverModel->update($driver['id'], $data);
            }

            $updatedDriver = $driverModel->find($driver['id']);
            unset($updatedDriver['password']);
            unset($updatedDriver['current_latitude'], $updatedDriver['current_longitude']);

            return $this->respond([
                'success' => true,
                'message' => 'Profile updated',
                'data'    => $updatedDriver
            ]);
        }

        return $this->respond([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    /**
     * Update driver location
     * POST /api/driver/location
     */
    public function updateLocation()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        $driverModel = new DriverModel();
        $driver = $driverModel->where('api_token', $token)->first();

        if (!$driver) {
            return $this->respond([
                'success' => false,
                'message' => 'Only drivers can update location'
            ], 403);
        }

        return $this->respond([
            'success' => true,
            'message' => 'Location tracking is disabled'
        ]);
    }

    /**
     * Update FCM token
     * POST /api/fcm-token
     */
    public function updateFcmToken()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        $fcmToken = $this->request->getJSON()->fcm_token ?? $this->request->getPost('fcm_token');

        if (!$fcmToken) {
            return $this->respond([
                'success' => false,
                'message' => 'FCM token required'
            ], 400);
        }

        // Check customer
        $customerModel = new CustomerModel();
        $customer = $customerModel->where('api_token', $token)->first();

        if ($customer) {
            $customerModel->update($customer['id'], ['fcm_token' => $fcmToken]);
            return $this->respond([
                'success' => true,
                'message' => 'FCM token updated'
            ]);
        }

        // Check driver
        $driverModel = new DriverModel();
        $driver = $driverModel->where('api_token', $token)->first();

        if ($driver) {
            $driverModel->update($driver['id'], ['fcm_token' => $fcmToken]);
            return $this->respond([
                'success' => true,
                'message' => 'FCM token updated'
            ]);
        }

        return $this->respond([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }
}
