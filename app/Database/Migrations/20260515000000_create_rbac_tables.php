<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRbacTables extends Migration
{
    public function up()
    {
        $this->createRolesTable();
        $this->createPermissionsTable();
        $this->createRolePermissionsTable();
        $this->addUserColumns();
        $this->seedPermissions();
        $this->seedCoreRoles();
        $this->backfillExistingUsers();
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('role_permissions')) {
            $this->forge->dropTable('role_permissions', true);
        }

        if ($db->tableExists('permissions')) {
            $this->forge->dropTable('permissions', true);
        }

        if ($db->tableExists('roles')) {
            $this->forge->dropTable('roles', true);
        }

        if ($db->tableExists('users')) {
            foreach (['name', 'username', 'role_id', 'restaurant_id'] as $column) {
                if ($db->fieldExists($column, 'users')) {
                    $this->forge->dropColumn('users', $column);
                }
            }
        }
    }

    protected function createRolesTable(): void
    {
        if ($this->db->tableExists('roles')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 140,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'scope' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'restaurant',
            ],
            'is_system' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('scope');
        $this->forge->createTable('roles', true);
    }

    protected function createPermissionsTable(): void
    {
        if ($this->db->tableExists('permissions')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'permission_key' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
            ],
            'label' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
            ],
            'module' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('permission_key');
        $this->forge->addKey(['module', 'sort_order']);
        $this->forge->createTable('permissions', true);
    }

    protected function createRolePermissionsTable(): void
    {
        if ($this->db->tableExists('role_permissions')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'role_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'permission_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        // Add individual keys for faster lookups; unique constraint enforces pair uniqueness
        $this->forge->addKey('role_id');
        $this->forge->addKey('permission_id');
        $this->forge->addUniqueKey(['role_id', 'permission_id']);
        $this->forge->createTable('role_permissions', true);
    }

    protected function addUserColumns(): void
    {
        if (! $this->db->tableExists('users')) {
            return;
        }

        $columns = [];

        if (! $this->db->fieldExists('name', 'users')) {
            $columns['name'] = [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ];
        }

        if (! $this->db->fieldExists('username', 'users')) {
            $columns['username'] = [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ];
        }

        if (! $this->db->fieldExists('role_id', 'users')) {
            $columns['role_id'] = [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ];
        }

        if (! $this->db->fieldExists('restaurant_id', 'users')) {
            $columns['restaurant_id'] = [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ];
        }

        if ($columns !== []) {
            $this->forge->addColumn('users', $columns);
        }

        $this->addIndexIfMissing('users', 'idx_users_role_id', 'role_id');
        $this->addIndexIfMissing('users', 'idx_users_restaurant_id', 'restaurant_id');
        $this->addIndexIfMissing('users', 'idx_users_username', 'username');
    }

    protected function seedPermissions(): void
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        if ($this->db->table('permissions')->countAllResults() > 0) {
            return;
        }

        $permissions = [
            // Admin dashboard permissions
            ['permission_key' => 'access_admin_dashboard', 'label' => 'Access Admin Dashboard', 'module' => 'Admin', 'description' => 'Open the admin dashboard home', 'sort_order' => 10],
            ['permission_key' => 'manage_roles', 'label' => 'Manage Roles', 'module' => 'Admin', 'description' => 'Create, edit, and manage custom roles', 'sort_order' => 20],
            ['permission_key' => 'manage_staff_accounts', 'label' => 'Manage Staff Accounts', 'module' => 'Admin', 'description' => 'Create and edit staff user accounts', 'sort_order' => 30],
            ['permission_key' => 'manage_restaurant_information', 'label' => 'Manage Restaurant Approvals', 'module' => 'Admin', 'description' => 'Review and approve pending restaurant registrations', 'sort_order' => 40],
            ['permission_key' => 'manage_drivers', 'label' => 'Manage Driver Approvals', 'module' => 'Admin', 'description' => 'Review and approve pending driver registrations', 'sort_order' => 50],
            ['permission_key' => 'view_orders', 'label' => 'View Order History', 'module' => 'Admin', 'description' => 'View delivered and completed orders on the admin panel', 'sort_order' => 60],
            // Restaurant dashboard permissions
            ['permission_key' => 'manage_menu_items', 'label' => 'Manage Menu Items', 'module' => 'Restaurant', 'description' => 'Create, edit, publish, and delete menu items', 'sort_order' => 70],
            ['permission_key' => 'accept_reject_orders', 'label' => 'Accept or Reject Orders', 'module' => 'Restaurant', 'description' => 'Approve or decline incoming customer orders', 'sort_order' => 80],
            ['permission_key' => 'prepare_orders', 'label' => 'Prepare Orders', 'module' => 'Restaurant', 'description' => 'Move orders into the preparation/kitchen flow', 'sort_order' => 90],
            ['permission_key' => 'update_order_status', 'label' => 'Update Order Status', 'module' => 'Restaurant', 'description' => 'Advance order status during fulfillment', 'sort_order' => 100],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($permissions as &$permission) {
            $permission['created_at'] = $now;
            $permission['updated_at'] = $now;
        }
        unset($permission);

        $this->db->table('permissions')->insertBatch($permissions);
    }

    protected function seedCoreRoles(): void
    {
        if (! $this->db->tableExists('roles') || ! $this->db->tableExists('permissions')) {
            return;
        }

        $roleTable = $this->db->table('roles');
        $permissionTable = $this->db->table('permissions');
        $now = date('Y-m-d H:i:s');

        $catalog = [
            [
                'name' => 'System Administrator',
                'slug' => 'admin',
                'scope' => 'admin',
                'description' => 'Full access to admin management features',
                'is_system' => 1,
                'permissions' => [
                    'access_admin_dashboard', 'manage_roles', 'manage_staff_accounts',
                    'manage_restaurant_information', 'manage_drivers', 'view_orders',
                ],
            ],
            [
                'name' => 'Restaurant Team Member',
                'slug' => 'restaurant',
                'scope' => 'restaurant',
                'description' => 'Default restaurant operations role',
                'is_system' => 1,
                'permissions' => [
                    'manage_menu_items', 'accept_reject_orders', 'prepare_orders',
                    'update_order_status', 'view_orders',
                ],
            ],
        ];

        foreach ($catalog as $item) {
            $role = $roleTable->where('slug', $item['slug'])->get()->getRowArray();
            $payload = [
                'name' => $item['name'],
                'slug' => $item['slug'],
                'description' => $item['description'],
                'scope' => $item['scope'],
                'is_system' => $item['is_system'],
                'is_active' => 1,
                'updated_at' => $now,
            ];

            if ($role) {
                $roleTable->where('id', (int) $role['id'])->update($payload);
                $roleId = (int) $role['id'];
            } else {
                $payload['created_at'] = $now;
                $roleTable->insert($payload);
                $roleId = (int) $this->db->insertID();
            }

            if ($roleId <= 0) {
                continue;
            }

            $permissionRows = $permissionTable
                ->select('id')
                ->whereIn('permission_key', $item['permissions'])
                ->get()
                ->getResultArray();

            $permissionIds = array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $permissionRows);

            if (empty($permissionIds)) {
                continue;
            }

            $existingAssignments = $this->db->table('role_permissions')
                ->select('permission_id')
                ->where('role_id', $roleId)
                ->get()
                ->getResultArray();

            $existingPermissionIds = array_map(static fn (array $row): int => (int) $row['permission_id'], $existingAssignments);
            $insertRows = [];

            foreach ($permissionIds as $permissionId) {
                $permissionId = (int) $permissionId;
                if (in_array($permissionId, $existingPermissionIds, true)) {
                    continue;
                }

                $insertRows[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($insertRows !== []) {
                $this->db->table('role_permissions')->insertBatch($insertRows);
            }
        }
    }

    protected function backfillExistingUsers(): void
    {
        if (! $this->db->tableExists('users') || ! $this->db->tableExists('roles') || ! $this->db->fieldExists('role_id', 'users')) {
            return;
        }

        $roleMap = [];
        foreach ($this->db->table('roles')->select('id, slug, scope')->get()->getResultArray() as $role) {
            $roleMap[(string) $role['slug']] = (int) $role['id'];
            if (! empty($role['scope'])) {
                $roleMap[(string) $role['scope']] = (int) $role['id'];
            }
        }

        foreach (['admin', 'restaurant'] as $legacyRole) {
            if (! isset($roleMap[$legacyRole])) {
                continue;
            }

            $this->db->table('users')
                ->where('role', $legacyRole)
                ->where('role_id', null)
                ->update(['role_id' => $roleMap[$legacyRole]]);
        }
    }

    protected function addIndexIfMissing(string $table, string $indexName, string $column): void
    {
        if (! $this->db->tableExists($table) || ! $this->db->fieldExists($column, $table)) {
            return;
        }

        $indexes = $this->db->getIndexData($table);
        foreach ($indexes as $index) {
            $existingName = '';
            if (is_array($index)) {
                $existingName = $index['name'] ?? '';
            } elseif (is_object($index)) {
                $existingName = $index->name ?? '';
            }

            if ($existingName === $indexName) {
                return;
            }
        }

        $this->db->query(sprintf('ALTER TABLE `%s` ADD INDEX `%s` (`%s`)', $table, $indexName, $column));
    }
}