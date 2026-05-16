<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $roleIds = [];
        if ($this->db->tableExists('roles')) {
            foreach ($this->db->table('roles')->select('id, slug, scope')->get()->getResultArray() as $role) {
                $roleIds[(string) $role['slug']] = (int) $role['id'];
                $roleIds[(string) $role['scope']] = (int) $role['id'];
            }
        }

        $data = [
            [
                'name' => 'System Admin',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => password_hash('AdminPass123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'role_id' => $roleIds['admin'] ?? null,
                'is_active' => 1,
            ],
            [
                'name' => 'Restaurant Owner',
                'username' => 'restaurant',
                'email' => 'restaurant@example.com',
                'password' => password_hash('RestaurantPass123', PASSWORD_DEFAULT),
                'role' => 'restaurant',
                'role_id' => $roleIds['restaurant'] ?? null,
                'is_active' => 1,
            ],
            // additional restaurant owners used by the system
            [
                'name' => 'Vester Laurel',
                'username' => 'vesterlaurel',
                'email' => 'vesterlaurel@gmail.com',
                'password' => password_hash('secret123', PASSWORD_DEFAULT),
                'role' => 'restaurant',
                'role_id' => $roleIds['restaurant'] ?? null,
                'is_active' => 1,
            ],
            [
                'name' => 'Owner Two',
                'username' => 'owner2',
                'email' => 'owner2@example.com',
                'password' => password_hash('secret123', PASSWORD_DEFAULT),
                'role' => 'restaurant',
                'role_id' => $roleIds['restaurant'] ?? null,
                'is_active' => 1,
            ],
        ];

        foreach ($data as $row) {
            // Check if user exists, update if so, otherwise insert
            $existing = $this->db->table('users')->where('email', $row['email'])->get()->getRow();
            if ($existing) {
                $this->db->table('users')->where('email', $row['email'])->update($row);
            } else {
                $this->db->table('users')->insert($row);
            }
        }
    }
}
