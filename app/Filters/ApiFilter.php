<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Models\CustomerModel;
use App\Models\DriverModel;

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

        // Check customer token
        $customerModel = new CustomerModel();
        $customer = $customerModel->where('api_token', $token)->first();
        
        if ($customer) {
            if (!(int) $customer['is_active']) {
                return $this->unauthorizedResponse('Account is disabled');
            }
            // Store user info for controller access
            $request->customer = $customer;
            $request->userType = 'customer';
            return;
        }

        // Check driver token
        $driverModel = new DriverModel();
        $driver = $driverModel->where('api_token', $token)->first();
        
        if ($driver) {
            if (!(int) $driver['is_active']) {
                return $this->unauthorizedResponse('Account is disabled');
            }
            $request->driver = $driver;
            $request->userType = 'driver';
            return;
        }

        return $this->unauthorizedResponse('Invalid or expired token');
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
