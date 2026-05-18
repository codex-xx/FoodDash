<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * One-time seeder to prune permissions that have no corresponding dashboard page
 * and re-sync the two built-in system roles to only the permissions that exist.
 */
class PrunePhantomPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        // ------------------------------------------------------------------ //
        // 1. Remove permissions whose dashboard pages do NOT exist yet
        // ------------------------------------------------------------------ //
        $toRemove = [
            'view_sales_reports',
            'access_analytics',
            'manage_payments',
            'manage_customers',
            'view_notifications',
        ];

        foreach ($toRemove as $key) {
            $perm = $db->table('permissions')->where('permission_key', $key)->get()->getRowArray();
            if ($perm) {
                $db->table('role_permissions')->where('permission_id', (int) $perm['id'])->delete();
                $db->table('permissions')->where('id', (int) $perm['id'])->delete();
                CLI::write("  Removed: {$key}", 'green');
            } else {
                CLI::write("  Already absent: {$key}", 'yellow');
            }
        }

        // ------------------------------------------------------------------ //
        // 2. Update labels / modules of the surviving permissions so they
        //    accurately reflect what the links do in the sidebar.
        // ------------------------------------------------------------------ //
        $updates = [
            'access_admin_dashboard'        => ['label' => 'Access Admin Dashboard',      'description' => 'Open the admin dashboard home',                           'module' => 'Admin',      'sort_order' => 10],
            'manage_roles'                  => ['label' => 'Manage Roles',                'description' => 'Create, edit, and manage custom roles',                   'module' => 'Admin',      'sort_order' => 20],
            'manage_staff_accounts'         => ['label' => 'Manage Staff Accounts',       'description' => 'Create and edit staff user accounts',                     'module' => 'Admin',      'sort_order' => 30],
            'manage_restaurant_information' => ['label' => 'Manage Restaurant Approvals', 'description' => 'Review and approve pending restaurant registrations',      'module' => 'Admin',      'sort_order' => 40],
            'manage_drivers'                => ['label' => 'Manage Driver Approvals',     'description' => 'Review and approve pending driver registrations',          'module' => 'Admin',      'sort_order' => 50],
            'view_orders'                   => ['label' => 'View Order History',          'description' => 'View delivered and completed orders on the admin panel',   'module' => 'Admin',      'sort_order' => 60],
            'manage_menu_items'             => ['label' => 'Manage Menu Items',           'description' => 'Create, edit, publish, and delete menu items',             'module' => 'Restaurant', 'sort_order' => 70],
            'accept_reject_orders'          => ['label' => 'Accept or Reject Orders',     'description' => 'Approve or decline incoming customer orders',              'module' => 'Restaurant', 'sort_order' => 80],
            'prepare_orders'                => ['label' => 'Prepare Orders',              'description' => 'Move orders into the preparation/kitchen flow',            'module' => 'Restaurant', 'sort_order' => 90],
            'update_order_status'           => ['label' => 'Update Order Status',         'description' => 'Advance order status during fulfillment',                  'module' => 'Restaurant', 'sort_order' => 100],
        ];

        foreach ($updates as $key => $data) {
            $data['updated_at'] = $now;
            $db->table('permissions')->where('permission_key', $key)->update($data);
            CLI::write("  Updated: {$key}", 'cyan');
        }

        // ------------------------------------------------------------------ //
        // 3. Re-sync the two built-in system roles
        // ------------------------------------------------------------------ //
        $systemRoles = [
            'admin'      => ['access_admin_dashboard', 'manage_roles', 'manage_staff_accounts', 'manage_restaurant_information', 'manage_drivers', 'view_orders'],
            'restaurant' => ['manage_menu_items', 'accept_reject_orders', 'prepare_orders', 'update_order_status', 'view_orders', 'manage_restaurant_information'],
        ];

        foreach ($systemRoles as $slug => $permKeys) {
            $role = $db->table('roles')->where('slug', $slug)->get()->getRowArray();
            if (! $role) {
                CLI::write("  Role not found: {$slug}", 'red');
                continue;
            }

            $roleId = (int) $role['id'];
            $db->table('role_permissions')->where('role_id', $roleId)->delete();

            $permRows = $db->table('permissions')
                ->select('id')
                ->whereIn('permission_key', $permKeys)
                ->get()->getResultArray();

            $insertRows = array_map(static fn (array $r): array => [
                'role_id'       => $roleId,
                'permission_id' => (int) $r['id'],
                'created_at'    => $now,
                'updated_at'    => $now,
            ], $permRows);

            if ($insertRows !== []) {
                $db->table('role_permissions')->insertBatch($insertRows);
            }

            CLI::write("  Role '{$role['name']}' — " . count($insertRows) . ' permissions assigned.', 'green');
        }

        CLI::write(PHP_EOL . 'Permission pruning complete.', 'white');
    }
}
