<?php

namespace App\Filters;

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
