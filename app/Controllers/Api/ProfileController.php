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
            $json = $this->getPayload();
            $data = [];

            foreach (['name', 'phone', 'address'] as $field) {
                if (array_key_exists($field, $json)) {
                    $data[$field] = $json[$field];
                }
            }

            if (array_key_exists('latitude', $json) && is_numeric($json['latitude'])) {
                $data['latitude'] = (float) $json['latitude'];
            }

            if (array_key_exists('longitude', $json) && is_numeric($json['longitude'])) {
                $data['longitude'] = (float) $json['longitude'];
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
            $json = $this->getPayload();
            $data = [];

            foreach (['name', 'phone', 'vehicle_type', 'address'] as $field) {
                if (array_key_exists($field, $json)) {
                    $data[$field] = $json[$field];
                }
            }

            if (array_key_exists('latitude', $json) && is_numeric($json['latitude'])) {
                $data['latitude'] = (float) $json['latitude'];
            }

            if (array_key_exists('longitude', $json) && is_numeric($json['longitude'])) {
                $data['longitude'] = (float) $json['longitude'];
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

        $payload = $this->getPayload();
        $location = $this->normalizeLocationPayload($payload);

        if (! empty($location['errors'])) {
            return $this->respond([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $location['errors'],
            ], 400);
        }

        $driverModel = new DriverModel();
        $driverModel->update($driver['id'], [
            'address' => $location['address'],
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
        ]);

        return $this->respond([
            'success' => true,
            'message' => 'Location saved successfully',
            'data' => [
                'address' => $location['address'],
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
            ],
        ]);
    }

    /**
     * Update customer location
     * POST /api/customer/location
     */
    public function updateCustomerLocation()
    {
        $customer = $this->request->customer ?? null;

        if (!$customer) {
            return $this->respond([
                'success' => false,
                'message' => 'Only customers can update location'
            ], 403);
        }

        $payload = $this->getPayload();
        $location = $this->normalizeLocationPayload($payload);

        if (! empty($location['errors'])) {
            return $this->respond([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $location['errors'],
            ], 400);
        }

        $customerModel = new CustomerModel();
        $customerModel->update($customer['id'], [
            'address' => $location['address'],
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
        ]);

        return $this->respond([
            'success' => true,
            'message' => 'Location saved successfully',
            'data' => [
                'address' => $location['address'],
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
            ],
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

    private function getPayload(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json) && ! empty($json)) {
            return $json;
        }

        $post = $this->request->getPost();
        return is_array($post) ? $post : [];
    }

    private function normalizeLocationPayload(array $payload): array
    {
        $address = trim((string) ($payload['address'] ?? $payload['full_address'] ?? $payload['location'] ?? ''));
        $latitudeValue = $payload['latitude'] ?? $payload['lat'] ?? null;
        $longitudeValue = $payload['longitude'] ?? $payload['lng'] ?? null;

        $latitude = is_numeric($latitudeValue) ? (float) $latitudeValue : null;
        $longitude = is_numeric($longitudeValue) ? (float) $longitudeValue : null;

        $errors = [];

        if ($address === '') {
            $errors['address'] = 'Address is required.';
        }

        if ($latitude === null) {
            $errors['latitude'] = 'Latitude is required and must be numeric.';
        }

        if ($longitude === null) {
            $errors['longitude'] = 'Longitude is required and must be numeric.';
        }

        return [
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'errors' => $errors,
        ];
    }
}
