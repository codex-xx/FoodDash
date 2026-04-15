<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Models\CustomerModel;
use App\Models\DriverModel;
use App\Models\AuthTokenModel;
use App\Libraries\JwtService;

class ApiFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $token = '';

        if (!empty($authHeader)) {
            // Extract Bearer token
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            } else {
                $token = $authHeader;
            }
        } else {
            // Legacy mobile clients pass token in query/body as api_token.
            $token = (string) ($request->getGet('api_token') ?? $request->getPost('api_token') ?? '');
        }

        if (empty($token)) {
            return $this->unauthorizedResponse('Authorization token required');
        }

        $token = trim($token);

        $jwtService = new JwtService();
        $claims = $jwtService->decodeAccessToken($token);

        if ($claims === null) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }

        $userType = $claims['type'] ?? null;
        $userId = isset($claims['uid']) ? (int) $claims['uid'] : 0;
        $tokenVersion = isset($claims['v']) ? (int) $claims['v'] : 0;
        $jti = isset($claims['jti']) ? (string) $claims['jti'] : '';

        if (!in_array($userType, ['customer', 'driver'], true) || $userId <= 0) {
            return $this->unauthorizedResponse('Invalid token payload');
        }

        if ($jti !== '' && $this->tableExists('auth_tokens')) {
            $authTokenModel = new AuthTokenModel();
            $session = $authTokenModel
                ->where('jti', $jti)
                ->where('user_type', $userType)
                ->where('user_id', $userId)
                ->first();

            if (!$session || !empty($session['revoked_at'])) {
                return $this->unauthorizedResponse('Session has been revoked');
            }

            if (!empty($session['expires_at']) && strtotime((string) $session['expires_at']) < time()) {
                return $this->unauthorizedResponse('Session has expired');
            }

            $authTokenModel->update((int) $session['id'], ['last_seen_at' => date('Y-m-d H:i:s')]);
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

    protected function tableExists(string $table): bool
    {
        try {
            return db_connect()->tableExists($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
