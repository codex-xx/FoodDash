<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'email' => 'admin@example.com',
                'password' => password_hash('AdminPass123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'is_active' => 1,
            ],
            [
                'email' => 'restaurant@example.com',
                'password' => password_hash('RestaurantPass123', PASSWORD_DEFAULT),
                'role' => 'restaurant',
                'is_active' => 1,
            ],
            // additional restaurant owners used by the system
            [
                'email' => 'vesterlaurel@gmail.com',
                'password' => password_hash('secret123', PASSWORD_DEFAULT),
                'role' => 'restaurant',
                'is_active' => 1,
            ],
            [
                'email' => 'owner2@example.com',
                'password' => password_hash('secret123', PASSWORD_DEFAULT),
                'role' => 'restaurant',
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
