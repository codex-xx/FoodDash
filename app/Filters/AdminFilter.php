<?php

namespace App\Filters;

use App\Libraries\SecurityAuditService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (! $session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            $security = new SecurityAuditService();
            $userId = is_numeric($session->get('user_id')) ? (int) $session->get('user_id') : null;
            $security->recordUnauthorizedAccess($request, $userId, 'Non-admin user attempted to access admin route');
            return redirect()->to('/login')->with('error', 'Access denied');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
