<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CustomerModel;
use App\Models\DriverModel;
use App\Libraries\ActivityLogger;

class ProfileController extends ResourceController
{
    protected $format = 'json';

    /**
     * Get current user profile
     * GET /api/profile
     */
    public function index()
    {
        $customer = $this->request->customer ?? null;

        if ($customer) {
            unset($customer['password']);
            return $this->respond([
                'success'   => true,
                'user_type' => 'customer',
                'data'      => $customer
            ]);
        }

        $driver = $this->request->driver ?? null;

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
        $customerModel = new CustomerModel();
        $customer = $this->request->customer ?? null;

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

                $logger = new ActivityLogger();
                $logger->logUserActivity(
                    $this->request,
                    'customer',
                    (int) $customer['id'],
                    'account_profile_updated',
                    'customer',
                    (int) $customer['id'],
                    ['fields' => array_keys($data)]
                );
            }

            $updatedCustomer = $customerModel->find($customer['id']);
            unset($updatedCustomer['password']);

            return $this->respond([
                'success' => true,
                'message' => 'Profile updated',
                'data'    => $updatedCustomer
            ]);
        }

        $driverModel = new DriverModel();
        $driver = $this->request->driver ?? null;

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

                $logger = new ActivityLogger();
                $logger->logUserActivity(
                    $this->request,
                    'driver',
                    (int) $driver['id'],
                    'account_profile_updated',
                    'driver',
                    (int) $driver['id'],
                    ['fields' => array_keys($data)]
                );
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
        $driver = $this->request->driver ?? null;

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
        $json = $this->request->getJSON(true);
        $fcmToken = (is_array($json) ? ($json['fcm_token'] ?? null) : null) ?? $this->request->getPost('fcm_token');

        if (!$fcmToken) {
            return $this->respond([
                'success' => false,
                'message' => 'FCM token required'
            ], 400);
        }

        $customerModel = new CustomerModel();
        $customer = $this->request->customer ?? null;

        if ($customer) {
            $customerModel->update($customer['id'], ['fcm_token' => $fcmToken]);

            $logger = new ActivityLogger();
            $logger->logUserActivity(
                $this->request,
                'customer',
                (int) $customer['id'],
                'account_fcm_token_updated',
                'customer',
                (int) $customer['id']
            );

            return $this->respond([
                'success' => true,
                'message' => 'FCM token updated'
            ]);
        }

        $driverModel = new DriverModel();
        $driver = $this->request->driver ?? null;

        if ($driver) {
            $driverModel->update($driver['id'], ['fcm_token' => $fcmToken]);

            $logger = new ActivityLogger();
            $logger->logUserActivity(
                $this->request,
                'driver',
                (int) $driver['id'],
                'account_fcm_token_updated',
                'driver',
                (int) $driver['id']
            );

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
