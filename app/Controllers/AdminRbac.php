<?php

namespace App\Controllers;

use App\Libraries\ActivityLogger;
use App\Libraries\PermissionService;
use App\Models\PermissionModel;
use App\Models\RestaurantModel;
use App\Models\RoleModel;
use App\Models\RolePermissionModel;
use App\Models\UserModel;

class AdminRbac extends BaseController
{
    protected RoleModel $roleModel;
    protected PermissionModel $permissionModel;
    protected RolePermissionModel $rolePermissionModel;
    protected UserModel $userModel;
    protected RestaurantModel $restaurantModel;
    protected PermissionService $permissions;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
        $this->permissionModel = new PermissionModel();
        $this->rolePermissionModel = new RolePermissionModel();
        $this->userModel = new UserModel();
        $this->restaurantModel = new RestaurantModel();
        $this->permissions = new PermissionService();
        helper(['form', 'url']);
    }

    public function index()
    {
        $canManageRoles = $this->can('manage_roles');
        $canManageStaffAccounts = $this->can('manage_staff_accounts');

        if (! $this->isAdmin() || (! $canManageRoles && ! $canManageStaffAccounts)) {
            return $this->renderUnauthorized('You do not have permission to manage roles and staff accounts.');
        }

        $db = db_connect();
        $roleSearch = trim((string) $this->request->getGet('role_search'));
        $roleScope = trim((string) $this->request->getGet('role_scope'));
        $userSearch = trim((string) $this->request->getGet('user_search'));
        $userStatus = trim((string) $this->request->getGet('user_status'));
        $userRoleId = (int) ($this->request->getGet('user_role_id') ?? 0);
        $requestedTab = strtolower(trim((string) $this->request->getGet('tab')));
        $activeTab = in_array($requestedTab, ['roles', 'users'], true) ? $requestedTab : ($canManageRoles ? 'roles' : 'users');

        $permissionGroups = $this->permissions->permissionCatalogByModule();
        $roles = $this->buildRoles($roleSearch, $roleScope);
        $users = $this->buildUsers($userSearch, $userStatus, $userRoleId);
        $restaurants = $this->restaurantModel
            ->select('id, name')
            ->orderBy('name', 'ASC')
            ->findAll();

        $auditLogs = [];
        if ($db->tableExists('user_activity_logs')) {
            $auditLogs = $db->table('user_activity_logs')
                ->select('user_type, user_id, activity_type, target_type, target_id, created_at')
                ->whereIn('activity_type', ['role_create', 'role_update', 'role_delete', 'staff_create', 'staff_update', 'staff_status_toggle'])
                ->orderBy('id', 'DESC')
                ->limit(25)
                ->get()
                ->getResultArray();
        }

        return view('admin/rbac/index', [
            'roles' => $roles,
            'users' => $users,
            'restaurants' => $restaurants,
            'permissionGroups' => $permissionGroups,
            'allPermissions' => $this->permissionModel->orderBy('module', 'ASC')->orderBy('sort_order', 'ASC')->findAll(),
            'auditLogs' => $auditLogs,
            'roleSearch' => $roleSearch,
            'roleScope' => $roleScope,
            'userSearch' => $userSearch,
            'userStatus' => $userStatus,
            'userRoleId' => $userRoleId,
            'canManageRoles' => $canManageRoles,
            'canManageStaffAccounts' => $canManageStaffAccounts,
            'activeTab' => $activeTab,
        ]);
    }

    public function saveRole()
    {
        if (! $this->isAdmin() || ! $this->can('manage_roles')) {
            return $this->renderUnauthorized('You do not have permission to manage roles.');
        }

        $roleId = (int) ($this->request->getPost('role_id') ?? 0);
        $name = trim((string) $this->request->getPost('name'));
        $scope = strtolower(trim((string) $this->request->getPost('scope')));
        $description = trim((string) $this->request->getPost('description'));
        $permissionKeys = (array) $this->request->getPost('permissions');
        $permissionKeys = array_values(array_unique(array_filter(array_map('strtolower', array_map('trim', $permissionKeys)))));

        // Server-side scope auto-detection: if scope wasn't sent correctly from the
        // form, derive it from the selected permissions so roles are never mis-scoped.
        $adminOnlyKeys = ['access_admin_dashboard', 'manage_roles', 'manage_staff_accounts',
                          'manage_restaurant_information', 'manage_drivers', 'view_orders'];
        $restaurantOnlyKeys = ['manage_menu_items', 'accept_reject_orders', 'prepare_orders', 'update_order_status'];
        $hasAdmin      = array_intersect($permissionKeys, $adminOnlyKeys) !== [];
        $hasRestaurant = array_intersect($permissionKeys, $restaurantOnlyKeys) !== [];

        if (! in_array($scope, ['admin', 'restaurant'], true)) {
            // Scope not supplied or invalid — derive from permissions
            $scope = $hasAdmin ? 'admin' : 'restaurant';
        } elseif ($hasAdmin && ! $hasRestaurant) {
            // Only admin permissions selected but scope was 'restaurant' (old form bug)
            $scope = 'admin';
        } elseif ($hasRestaurant && ! $hasAdmin) {
            // Only restaurant permissions selected
            $scope = 'restaurant';
        }
        // Mixed or no permissions: trust the submitted scope value.

        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Role name is required.');
        }

        $roleSlug = $this->makeSlug($name);
        $existing = $this->roleModel->where('slug', $roleSlug)->first();
        if ($existing && (int) $existing['id'] !== $roleId) {
            return redirect()->back()->withInput()->with('error', 'A role with this name already exists.');
        }

        $payload = [
            'name' => $name,
            'slug' => $roleSlug,
            'description' => $description !== '' ? $description : null,
            'scope' => $scope,
            'is_system' => 0,
            'is_active' => 1,
        ];

        if ($roleId > 0) {
            $current = $this->roleModel->find($roleId);
            if (! $current) {
                return redirect()->back()->withInput()->with('error', 'Role not found.');
            }

            if ((int) ($current['is_system'] ?? 0) === 1 && $current['slug'] !== $roleSlug) {
                return redirect()->back()->withInput()->with('error', 'System role slug cannot be changed.');
            }

            $this->roleModel->update($roleId, $payload);
        } else {
            $roleId = (int) $this->roleModel->insert($payload, true);
        }

        $this->syncRolePermissions($roleId, $permissionKeys);
        $this->logRbacActivity($roleId > 0 && $this->request->getPost('role_id') ? 'role_update' : 'role_create', 'roles', $roleId, [
            'name' => $name,
            'scope' => $scope,
            'permissions' => $permissionKeys,
        ]);

        return redirect()->to('/admin/rbac?tab=roles')->with('success', 'Role saved successfully.');
    }

    public function deleteRole(int $id)
    {
        if (! $this->isAdmin() || ! $this->can('manage_roles')) {
            return $this->renderUnauthorized('You do not have permission to delete roles.');
        }

        $role = $this->roleModel->find($id);
        if (! $role) {
            return redirect()->to('/admin/rbac')->with('error', 'Role not found.');
        }

        if ((int) ($role['is_system'] ?? 0) === 1) {
            return redirect()->to('/admin/rbac')->with('error', 'System roles cannot be deleted.');
        }

        $inUse = $this->userModel->where('role_id', $id)->countAllResults();
        if ($inUse > 0) {
            return redirect()->to('/admin/rbac')->with('error', 'This role is assigned to users and cannot be deleted.');
        }

        $this->rolePermissionModel->where('role_id', $id)->delete();
        $this->roleModel->delete($id);
        $this->logRbacActivity('role_delete', 'roles', $id, ['name' => $role['name'] ?? '', 'scope' => $role['scope'] ?? '']);

        return redirect()->to('/admin/rbac')->with('success', 'Role deleted successfully.');
    }

    public function previewRole(int $id)
    {
        if (! $this->isAdmin() || ! $this->can('manage_roles')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $role = $this->roleModel->find($id);
        if (! $role) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Role not found']);
        }

        return $this->response->setJSON([
            'success' => true,
            'role' => $role,
            'permissions' => $this->permissions->permissionsForRoleId($id),
            'users_count' => (int) $this->userModel->where('role_id', $id)->countAllResults(),
        ]);
    }

    public function saveUser()
    {
        if (! $this->isAdmin() || ! $this->can('manage_staff_accounts')) {
            return $this->renderUnauthorized('You do not have permission to manage staff accounts.');
        }

        $userId = (int) ($this->request->getPost('user_id') ?? 0);
        $name = trim((string) $this->request->getPost('name'));
        $email = trim((string) $this->request->getPost('email'));
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $roleId = (int) ($this->request->getPost('role_id') ?? 0);
        $restaurantId = (int) ($this->request->getPost('restaurant_id') ?? 0);
        $isActive = $this->request->getPost('is_active') ? 1 : 0;

        if ($name === '' || $email === '' || $username === '' || $roleId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Name, email, username, and role are required.');
        }

        $role = $this->roleModel->find($roleId);
        if (! $role) {
            return redirect()->back()->withInput()->with('error', 'Please choose a valid role.');
        }

        if ($userId <= 0 && $password === '') {
            return redirect()->back()->withInput()->with('error', 'Password is required for a new account.');
        }

        if ($this->emailExists($email, $userId)) {
            return redirect()->back()->withInput()->with('error', 'Email already exists.');
        }

        if ($this->usernameExists($username, $userId)) {
            return redirect()->back()->withInput()->with('error', 'Username already exists.');
        }

        $payload = [
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'role' => (string) ($role['scope'] ?? 'restaurant'),
            'role_id' => $roleId,
            'restaurant_id' => ($role['scope'] ?? '') === 'restaurant' && $restaurantId > 0 ? $restaurantId : null,
            'is_active' => $isActive,
        ];

        if ($password !== '') {
            $payload['password'] = $password;
        }

        if ($userId > 0) {
            $this->userModel->update($userId, $payload);
            $activity = 'staff_update';
        } else {
            $userId = (int) $this->userModel->insert($payload, true);
            $activity = 'staff_create';
        }

        $this->logRbacActivity($activity, 'users', $userId, [
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'role_id' => $roleId,
            'restaurant_id' => $payload['restaurant_id'],
            'is_active' => $isActive,
        ]);

        return redirect()->to('/admin/rbac?tab=users')->with('success', 'User saved successfully.');
    }

    public function toggleUserStatus(int $id)
    {
        if (! $this->isAdmin() || ! $this->can('manage_staff_accounts')) {
            return $this->renderUnauthorized('You do not have permission to change staff status.');
        }

        $user = $this->userModel->find($id);
        if (! $user) {
            return redirect()->to('/admin/rbac')->with('error', 'User not found.');
        }

        if (strtolower($user['role'] ?? '') === 'admin') {
            return redirect()->to('/admin/rbac?tab=users')->with('error', 'You cannot deactivate the main administrator account.');
        }

        $nextState = (int) ($user['is_active'] ?? 0) ? 0 : 1;
        $this->userModel->update($id, ['is_active' => $nextState]);
        $this->logRbacActivity('staff_status_toggle', 'users', $id, ['is_active' => $nextState]);

        return redirect()->to('/admin/rbac?tab=users')->with('success', $nextState ? 'User activated.' : 'User deactivated.');
    }

    public function previewUser(int $id)
    {
        if (! $this->isAdmin() || ! $this->can('manage_staff_accounts')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $user = $this->userModel->find($id);
        if (! $user) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User not found']);
        }

        return $this->response->setJSON([
            'success' => true,
            'user' => $user,
            'role' => $user['role_id'] ? $this->roleModel->find((int) $user['role_id']) : null,
        ]);
    }

    protected function buildRoles(string $search, string $scope): array
    {
        $builder = $this->roleModel->builder();
        $builder->select('roles.*, COUNT(DISTINCT users.id) AS users_count, COUNT(DISTINCT role_permissions.permission_id) AS permissions_count')
            ->join('users', 'users.role_id = roles.id', 'left')
            ->join('role_permissions', 'role_permissions.role_id = roles.id', 'left')
            ->groupBy('roles.id')
            ->orderBy('roles.is_system', 'DESC')
            ->orderBy('roles.name', 'ASC');

        if ($search !== '') {
            $builder->groupStart()
                ->like('roles.name', $search)
                ->orLike('roles.slug', $search)
                ->orLike('roles.description', $search)
                ->groupEnd();
        }

        if (in_array($scope, ['admin', 'restaurant'], true)) {
            $builder->where('roles.scope', $scope);
        }

        $roles = $builder->get()->getResultArray();
        foreach ($roles as &$role) {
            $role['permissions'] = $this->permissions->permissionsForRoleId((int) $role['id']);
            $role['permission_labels'] = $this->labelsForPermissionKeys($role['permissions']);
        }
        unset($role);

        return $roles;
    }

    protected function buildUsers(string $search, string $status, int $roleId): array
    {
        $builder = $this->userModel->builder();
        $builder->select('users.*, roles.name as role_name, roles.slug as role_slug, roles.scope as role_scope, restaurants.name as restaurant_name')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->join('restaurants', 'restaurants.id = users.restaurant_id', 'left')
            ->orderBy('users.id', 'DESC');

        if ($search !== '') {
            $builder->groupStart()
                ->like('users.name', $search)
                ->orLike('users.email', $search)
                ->orLike('users.username', $search)
                ->orLike('roles.name', $search)
                ->groupEnd();
        }

        if ($status === 'active') {
            $builder->where('users.is_active', 1);
        } elseif ($status === 'inactive') {
            $builder->where('users.is_active', 0);
        }

        if ($roleId > 0) {
            $builder->where('users.role_id', $roleId);
        }

        $users = $builder->get()->getResultArray();
        foreach ($users as &$user) {
            $user['access_profile'] = $this->permissions->resolveUserAccess($user);
        }
        unset($user);

        return $users;
    }

    protected function syncRolePermissions(int $roleId, array $permissionKeys): void
    {
        $this->rolePermissionModel->where('role_id', $roleId)->delete();

        if ($permissionKeys === []) {
            return;
        }

        // Self-heal: auto-insert any permission keys that are missing from the
        // permissions table so that custom roles always save their full selection.
        $existing = $this->permissionModel
            ->select('permission_key')
            ->whereIn('permission_key', $permissionKeys)
            ->findAll();

        $existingKeys = array_column($existing, 'permission_key');
        $missingKeys  = array_diff($permissionKeys, $existingKeys);

        if ($missingKeys !== []) {
            $catalog = [];
            foreach ((new \App\Libraries\PermissionService())->permissionCatalog() as $entry) {
                $catalog[strtolower(trim((string) ($entry['permission_key'] ?? '')))] = $entry;
            }

            $now = date('Y-m-d H:i:s');
            foreach ($missingKeys as $missingKey) {
                $missingKey = strtolower(trim($missingKey));
                $catalogEntry = $catalog[$missingKey] ?? [];
                $this->permissionModel->insert([
                    'permission_key' => $missingKey,
                    'label'          => (string) ($catalogEntry['label']       ?? ucwords(str_replace('_', ' ', $missingKey))),
                    'module'         => (string) ($catalogEntry['module']      ?? 'General'),
                    'description'    => (string) ($catalogEntry['description'] ?? ''),
                    'sort_order'     => (int)    ($catalogEntry['sort_order']  ?? 99),
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }

        // Now fetch all matching permission IDs (including newly inserted ones)
        $permissions = $this->permissionModel
            ->select('id, permission_key')
            ->whereIn('permission_key', $permissionKeys)
            ->findAll();

        $rows = [];
        $now  = date('Y-m-d H:i:s');
        foreach ($permissions as $permission) {
            $rows[] = [
                'role_id'       => $roleId,
                'permission_id' => (int) $permission['id'],
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        if ($rows !== []) {
            $this->rolePermissionModel->insertBatch($rows);
        }
    }

    protected function labelsForPermissionKeys(array $permissionKeys): array
    {
        $lookup = [];
        foreach ($this->permissionModel->findAll() as $permission) {
            $lookup[strtolower((string) $permission['permission_key'])] = (string) $permission['label'];
        }

        $labels = [];
        foreach ($permissionKeys as $permissionKey) {
            $labels[] = $lookup[strtolower((string) $permissionKey)] ?? (string) $permissionKey;
        }

        return $labels;
    }

    protected function emailExists(string $email, int $ignoreId = 0): bool
    {
        $builder = $this->userModel->builder();
        $builder->where('email', $email);
        if ($ignoreId > 0) {
            $builder->where('id !=', $ignoreId);
        }

        return $builder->countAllResults() > 0;
    }

    protected function usernameExists(string $username, int $ignoreId = 0): bool
    {
        $db = db_connect();
        if (! $db->fieldExists('username', 'users')) {
            return false;
        }

        $builder = $this->userModel->builder();
        $builder->where('username', $username);
        if ($ignoreId > 0) {
            $builder->where('id !=', $ignoreId);
        }

        return $builder->countAllResults() > 0;
    }

    protected function makeSlug(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $value) ?? ''));
        return trim($slug, '-') ?: 'role-' . time();
    }

    protected function logRbacActivity(string $activityType, string $targetType, int $targetId, array $meta = []): void
    {
        try {
            $logger = new ActivityLogger();
            $logger->logUserActivity(
                $this->request,
                'admin',
                (int) (session()->get('user_id') ?? 0),
                $activityType,
                $targetType,
                $targetId,
                $meta
            );
        } catch (\Throwable $e) {
            log_message('error', 'Failed to log RBAC activity: ' . $e->getMessage());
        }
    }

    protected function can(string $permission): bool
    {
        return $this->permissions->hasPermission($permission);
    }

    protected function isAdmin(): bool
    {
        return (bool) session()->get('isLoggedIn') && (string) session()->get('role') === 'admin';
    }

    protected function renderUnauthorized(string $message)
    {
        return $this->response
            ->setStatusCode(403)
            ->setBody(view('errors/unauthorized', ['message' => $message]));
    }
}