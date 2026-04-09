<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Models\CustomerModel;
use App\Models\DriverModel;
use App\Libraries\JwtService;

class ApiFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return $this->unauthorizedResponse('Authorization token required');
        }

        // Extract Bearer token
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        if (empty($token)) {
            return $this->unauthorizedResponse('Invalid token format');
        }

        $jwtService = new JwtService();
        $claims = $jwtService->decodeAccessToken($token);

        if ($claims === null) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }

        $userType = $claims['type'] ?? null;
        $userId = isset($claims['uid']) ? (int) $claims['uid'] : 0;
        $tokenVersion = isset($claims['v']) ? (int) $claims['v'] : 0;

        if (!in_array($userType, ['customer', 'driver'], true) || $userId <= 0) {
            return $this->unauthorizedResponse('Invalid token payload');
        }

        if ($userType === 'customer') {
            $customerModel = new CustomerModel();
            $customer = $customerModel->find($userId);

            if (!$customer || !(int) ($customer['is_active'] ?? 0)) {
                return $this->unauthorizedResponse('Account is disabled');
            }

            if ((int) ($customer['token_version'] ?? 0) !== $tokenVersion) {
                return $this->unauthorizedResponse('Token has been invalidated');
            }

            $request->customer = $customer;
            $request->userType = 'customer';
            $request->tokenClaims = $claims;
            return;
        }

        $driverModel = new DriverModel();
        $driver = $driverModel->find($userId);

        if (!$driver || !(int) ($driver['is_active'] ?? 0)) {
            return $this->unauthorizedResponse('Account is disabled');
        }

        if ((int) ($driver['token_version'] ?? 0) !== $tokenVersion) {
            return $this->unauthorizedResponse('Token has been invalidated');
        }

        $request->driver = $driver;
        $request->userType = 'driver';
        $request->tokenClaims = $claims;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add CORS headers for API responses
        return $response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    }

    protected function unauthorizedResponse(string $message)
    {
        return service('response')
            ->setJSON([
                'success' => false,
                'message' => $message
            ])
            ->setStatusCode(401);
    }
}
