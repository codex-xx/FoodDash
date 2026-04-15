<?php

namespace App\Filters;

use App\Libraries\SecurityAuditService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AuthFilter implements FilterInterface
{
    // Session timeout in seconds (30 minutes)
    protected $timeout = 1800;

    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $security = new SecurityAuditService();

        if (! $session->get('isLoggedIn')) {
            $security->recordUnauthorizedAccess($request, null, 'Unauthenticated access attempt to protected route');
            return redirect()->to('/login')->with('error', 'Please login to continue');
        }

        $last = $session->get('last_activity') ?? 0;

        if (time() - $last > $this->timeout) {
            $security->logEvent($request, is_numeric($session->get('user_id')) ? (int) $session->get('user_id') : null, 'session_timeout', 'Session expired due to inactivity', 'low');
            $session->destroy();
            return redirect()->to('/login')->with('error', 'Session timed out. Please login again.');
        }

        // Refresh last activity
        $session->set('last_activity', time());

        $this->refreshTrackedWebSession((string) ($session->get('session_jti') ?? ''));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }

    protected function refreshTrackedWebSession(string $jti): void
    {
        if ($jti === '' || ! $this->tableExists('auth_tokens')) {
            return;
        }

        try {
            $db = db_connect();
            $idColumn = $db->fieldExists('jti', 'auth_tokens') ? 'jti' : 'jwt_id';

            $row = $db->table('auth_tokens')
                ->where($idColumn, $jti)
                ->get()
                ->getRowArray();

            if (! $row) {
                return;
            }

            $db->table('auth_tokens')
                ->where('id', (int) $row['id'])
                ->update([
                    'last_seen_at' => date('Y-m-d H:i:s'),
                    'expires_at' => date('Y-m-d H:i:s', time() + $this->timeout),
                ]);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to refresh tracked web session: ' . $e->getMessage());
        }
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
