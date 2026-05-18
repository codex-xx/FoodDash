<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackfillSeededUserMfaFlags extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('users') || ! $this->db->fieldExists('mfa_enabled', 'users')) {
            return;
        }

        $users = [
            'admin@example.com' => 1,
            'restaurant@example.com' => 0,
            'vesterlaurel@gmail.com' => 0,
            'owner2@example.com' => 0,
        ];

        foreach ($users as $email => $mfaEnabled) {
            $existing = $this->db->table('users')->where('email', $email)->get()->getRowArray();
            if (! $existing) {
                continue;
            }

            $this->db->table('users')
                ->where('email', $email)
                ->update([
                    'mfa_enabled' => $mfaEnabled,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('users') || ! $this->db->fieldExists('mfa_enabled', 'users')) {
            return;
        }

        foreach (['admin@example.com', 'restaurant@example.com', 'vesterlaurel@gmail.com', 'owner2@example.com'] as $email) {
            $existing = $this->db->table('users')->where('email', $email)->get()->getRowArray();
            if (! $existing) {
                continue;
            }

            $this->db->table('users')
                ->where('email', $email)
                ->update([
                    'mfa_enabled' => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }
}