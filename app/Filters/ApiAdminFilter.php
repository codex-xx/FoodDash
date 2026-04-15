<?php

namespace App\Filters;

use App\Libraries\SecurityAuditService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if ((bool) $session->get('isLoggedIn') && (string) $session->get('role') === 'admin') {
            return;
        }

        $security = new SecurityAuditService();
        $userId = is_numeric($session->get('user_id')) ? (int) $session->get('user_id') : null;
        $security->recordUnauthorizedAccess($request, $userId, 'Unauthorized API admin endpoint access');

        return service('response')
            ->setJSON([
                'success' => false,
                'message' => 'Admin authorization required',
            ])
            ->setStatusCode(403);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
