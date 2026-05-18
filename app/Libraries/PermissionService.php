<?php

namespace App\Libraries;

use App\Models\PermissionModel;
use App\Models\RoleModel;
use App\Models\RolePermissionModel;
use App\Models\UserModel;

class PermissionService
{
    private array $legacyMatrix = [
        'admin' => [
            'orders' => ['read', 'write', 'update', 'delete', 'assign'],
            'menus' => ['read', 'write', 'update', 'delete'],
            'users' => ['read', 'write', 'update', 'delete'],
            'drivers' => ['read', 'write', 'update', 'delete'],
            'restaurants' => ['read', 'write', 'update', 'delete'],
            'profile' => ['read', 'update'],
        ],
        'restaurant' => [
            'orders' => ['read', 'update'],
            'menus' => ['read', 'write', 'update', 'delete'],
            'profile' => ['read', 'update'],
        ],
        'driver' => [
            'orders' => ['read', 'update', 'assign'],
            'profile' => ['read', 'update'],
        ],
        'customer' => [
            'orders' => ['read', 'write', 'update'],
            'profile' => ['read', 'update'],
        ],
    ];

    private array $resourceActionMap = [
        'orders' => [
            'read' => 'view_orders',
            'write' => 'accept_reject_orders',
            'update' => 'update_order_status',
            'delete' => 'update_order_status',
            'assign' => 'manage_drivers',
        ],
        'menus' => [
            'read' => 'view_orders',
            'write' => 'manage_menu_items',
            'update' => 'manage_menu_items',
            'delete' => 'manage_menu_items',
        ],
        'users' => [
            'read' => 'manage_staff_accounts',
            'write' => 'manage_staff_accounts',
            'update' => 'manage_staff_accounts',
            'delete' => 'manage_staff_accounts',
        ],
        'drivers' => [
            'read' => 'manage_drivers',
            'write' => 'manage_drivers',
            'update' => 'manage_drivers',
            'delete' => 'manage_drivers',
        ],
        'restaurants' => [
            'read' => 'manage_restaurant_information',
            'write' => 'manage_restaurant_information',
            'update' => 'manage_restaurant_information',
            'delete' => 'manage_restaurant_information',
        ],
        'profile' => [
            'read' => 'manage_staff_accounts',
            'update' => 'manage_staff_accounts',
        ],
    ];

    public function permissionCatalog(): array
    {
        if ($this->tableExists('permissions')) {
            $rows = (new PermissionModel())
                ->orderBy('module', 'ASC')
                ->orderBy('sort_order', 'ASC')
                ->orderBy('label', 'ASC')
                ->findAll();

            if ($rows !== []) {
                return $rows;
            }
        }

        return $this->fallbackPermissionCatalog();
    }

    public function permissionCatalogByModule(): array
    {
        $grouped = [];
        foreach ($this->permissionCatalog() as $permission) {
            $module = (string) ($permission['module'] ?? 'General');
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }

    public function resolveUserAccess(array $user): array
    {
        $userRoleId = isset($user['role_id']) && is_numeric($user['role_id']) ? (int) $user['role_id'] : null;
        $legacyRole = strtolower(trim((string) ($user['role'] ?? 'restaurant')));

        $roleRow = null;
        if ($userRoleId !== null && $this->tableExists('roles')) {
            $roleRow = (new RoleModel())->find($userRoleId);
        }

        // Only attempt slug/scope fallback lookup if no role_id was supplied
        if (! $roleRow && $userRoleId === null && $this->tableExists('roles')) {
            $roleRow = (new RoleModel())
                ->groupStart()
                ->where('slug', $legacyRole)
                ->orWhere('scope', $legacyRole)
                ->groupEnd()
                ->first();
        }

        $permissionKeys   = [];
        $roleFoundInDb    = $roleRow !== null && $this->tableExists('permissions') && $this->tableExists('role_permissions');
        $isSystemRole     = $roleFoundInDb && (int) ($roleRow['is_system'] ?? 0) === 1;

        if ($roleFoundInDb) {
            $permissionKeys = $this->permissionsForRoleId((int) $roleRow['id']);

            $roleScope = strtolower(trim((string) ($roleRow['scope'] ?? '')));
            if ($roleScope === 'admin' && ! in_array('access_admin_dashboard', $permissionKeys, true)) {
                array_unshift($permissionKeys, 'access_admin_dashboard');
            }

            // For system roles only: if the role_permissions table has no entries
            // (e.g. DB was seeded without them), fall back to the built-in legacy
            // matrix so built-in admin/restaurant accounts never lose their access.
            if ($permissionKeys === [] && $isSystemRole) {
                $permissionKeys = $this->legacyPermissionsForRole($legacyRole);
            }
            // Custom roles (is_system = 0) intentionally keep their DB-assigned
            // permissions — even if empty. No legacy escalation for custom roles.
        } else {
            // No RBAC role row found at all — use the built-in legacy matrix so
            // the original admin/restaurant accounts keep working.
            $permissionKeys = $this->legacyPermissionsForRole($legacyRole);
        }

        $permissionKeys = array_values(array_unique(array_map(static fn (string $key): string => strtolower(trim($key)), $permissionKeys)));

        return [
            'role_id'           => $roleRow ? (int) $roleRow['id'] : $userRoleId,
            'role_scope'        => (string) ($roleRow['scope'] ?? $legacyRole),
            'role_name'         => (string) ($roleRow['name'] ?? ucfirst($legacyRole)),
            'role_slug'         => (string) ($roleRow['slug'] ?? $legacyRole),
            'permission_keys'   => $permissionKeys,
            'permission_labels' => $this->labelsForPermissionKeys($permissionKeys),
            'restaurant_id'     => isset($user['restaurant_id']) && is_numeric($user['restaurant_id']) ? (int) $user['restaurant_id'] : null,
        ];
    }


    public function permissionsForRoleId(int $roleId): array
    {
        if ($roleId <= 0 || ! $this->tableExists('role_permissions') || ! $this->tableExists('permissions')) {
            return [];
        }

        $rows = (new RolePermissionModel())
            ->select('permissions.permission_key')
            ->join('permissions', 'permissions.id = role_permissions.permission_id', 'inner')
            ->where('role_permissions.role_id', $roleId)
            ->orderBy('permissions.module', 'ASC')
            ->orderBy('permissions.sort_order', 'ASC')
            ->findAll();

        return array_values(array_filter(array_map(static function (array $row): string {
            return strtolower(trim((string) ($row['permission_key'] ?? '')));
        }, $rows)));
    }

    public function currentPermissionKeys(?array $sessionData = null): array
    {
        $sessionData = $sessionData ?? session()->get() ?? [];
        if (! is_array($sessionData)) {
            $sessionData = [];
        }
        if (array_key_exists('permission_keys', $sessionData) && is_array($sessionData['permission_keys']) && $sessionData['permission_keys'] !== []) {
            $keys = $sessionData['permission_keys'];

            return array_values(array_unique(array_map(static fn ($key): string => strtolower(trim((string) $key)), $keys)));
        }

        // Session has no permission_keys (or is empty). Re-resolve from the DB via
        // the full user record so all fallback logic (including system-role legacy
        // fallback) is applied consistently.
        $userId = isset($sessionData['user_id']) && is_numeric($sessionData['user_id']) ? (int) $sessionData['user_id'] : null;
        if ($userId === null || ! $this->tableExists('users')) {
            return [];
        }

        $user = (new UserModel())->find($userId);
        if (! $user) {
            return [];
        }

        $keys = $this->resolveUserAccess($user)['permission_keys'] ?? [];

        // Cache resolved keys back into the session for subsequent requests
        if ($keys !== []) {
            try {
                session()->set('permission_keys', $keys);
            } catch (\Throwable $e) {
                // ignore session write errors
            }
        }

        return $keys;
    }

    public function hasPermission(string $permissionKey, ?array $sessionData = null): bool
    {
        $permissionKey = strtolower(trim($permissionKey));
        if ($permissionKey === '') {
            return false;
        }

        $keys = $this->currentPermissionKeys($sessionData);
        if ($keys !== []) {
            return in_array($permissionKey, $keys, true);
        }

        $sessionData = $sessionData ?? session()->get() ?? [];
        if (! is_array($sessionData)) {
            $sessionData = [];
        }

        $scope = strtolower(trim((string) ($sessionData['role'] ?? '')));
        return in_array($permissionKey, $this->legacyPermissionsForRole($scope), true);
    }

    public function allows(?string $role, string $resource, string $action): bool
    {
        $role = strtolower(trim((string) $role));
        $resource = strtolower(trim($resource));
        $action = strtolower(trim($action));

        if ($role === '' || $resource === '' || $action === '') {
            return false;
        }

        $permissionKey = $this->resourceActionMap[$resource][$action] ?? null;
        if ($permissionKey !== null && $this->hasPermission($permissionKey)) {
            return true;
        }

        $allowedActions = $this->legacyMatrix[$role][$resource] ?? [];

        return in_array($action, $allowedActions, true);
    }

    protected function fallbackPermissionCatalog(): array
    {
        return [
            // Admin dashboard features
            ['permission_key' => 'access_admin_dashboard', 'label' => 'Access Admin Dashboard', 'module' => 'Admin', 'description' => 'Open the admin dashboard home', 'sort_order' => 10],
            ['permission_key' => 'manage_roles', 'label' => 'Manage Roles', 'module' => 'Admin', 'description' => 'Create, edit, and manage custom roles', 'sort_order' => 20],
            ['permission_key' => 'manage_staff_accounts', 'label' => 'Manage Staff Accounts', 'module' => 'Admin', 'description' => 'Create and edit staff user accounts', 'sort_order' => 30],
            ['permission_key' => 'manage_restaurant_information', 'label' => 'Manage Restaurant Approvals', 'module' => 'Admin', 'description' => 'Review and approve pending restaurant registrations', 'sort_order' => 40],
            ['permission_key' => 'manage_drivers', 'label' => 'Manage Driver Approvals', 'module' => 'Admin', 'description' => 'Review and approve pending driver registrations', 'sort_order' => 50],
            ['permission_key' => 'view_orders', 'label' => 'View Order History', 'module' => 'Admin', 'description' => 'View delivered and completed orders on the admin panel', 'sort_order' => 60],
            // Restaurant dashboard features
            ['permission_key' => 'manage_menu_items', 'label' => 'Manage Menu Items', 'module' => 'Restaurant', 'description' => 'Create, edit, publish, and delete menu items', 'sort_order' => 70],
            ['permission_key' => 'accept_reject_orders', 'label' => 'Accept or Reject Orders', 'module' => 'Restaurant', 'description' => 'Approve or decline incoming customer orders', 'sort_order' => 80],
            ['permission_key' => 'prepare_orders', 'label' => 'Prepare Orders', 'module' => 'Restaurant', 'description' => 'Move orders into the preparation/kitchen flow', 'sort_order' => 90],
            ['permission_key' => 'update_order_status', 'label' => 'Update Order Status', 'module' => 'Restaurant', 'description' => 'Advance order status during fulfillment', 'sort_order' => 100],
        ];
    }

    protected function labelsForPermissionKeys(array $permissionKeys): array
    {
        $lookup = [];
        foreach ($this->permissionCatalog() as $permission) {
            $lookup[strtolower((string) ($permission['permission_key'] ?? ''))] = (string) ($permission['label'] ?? '');
        }

        $labels = [];
        foreach ($permissionKeys as $permissionKey) {
            $labels[] = $lookup[strtolower(trim((string) $permissionKey))] ?? (string) $permissionKey;
        }

        return $labels;
    }

    protected function legacyPermissionsForRole(string $role): array
    {
        $role = strtolower(trim($role));

        return match ($role) {
            'admin' => [
                'access_admin_dashboard', 'manage_roles', 'manage_staff_accounts',
                'manage_restaurant_information', 'manage_drivers', 'view_orders',
            ],
            'restaurant' => [
                'manage_menu_items', 'accept_reject_orders', 'prepare_orders',
                'update_order_status', 'view_orders', 'manage_restaurant_information',
            ],
            'driver' => ['view_orders', 'update_order_status'],
            'customer' => ['view_orders'],
            default => [],
        };
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

