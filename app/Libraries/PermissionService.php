<?php

namespace App\Libraries;

class PermissionService
{
    /**
     * Basic role-resource-action matrix.
     * Actions are normalized to CRUD-oriented verbs where possible.
     */
    private array $matrix = [
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

    public function allows(?string $role, string $resource, string $action): bool
    {
        $role = strtolower(trim((string) $role));
        $resource = strtolower(trim($resource));
        $action = strtolower(trim($action));

        if ($role === '' || $resource === '' || $action === '') {
            return false;
        }

        $allowedActions = $this->matrix[$role][$resource] ?? [];

        return in_array($action, $allowedActions, true);
    }
}
