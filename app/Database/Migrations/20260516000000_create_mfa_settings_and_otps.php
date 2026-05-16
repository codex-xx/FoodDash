<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMfaSettingsAndOtps extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('app_settings')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'setting_key' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'setting_value' => [
                    'type' => 'TEXT',
                    'null' => true,
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
            $this->forge->addUniqueKey('setting_key');
            $this->forge->createTable('app_settings', true);
        }

        if (! $this->db->tableExists('mfa_otps')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 191,
                ],
                'challenge_token' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                ],
                'otp_hash' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                ],
                'purpose' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'default' => 'login',
                ],
                'attempts' => [
                    'type' => 'TINYINT',
                    'constraint' => 3,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'expires_at' => [
                    'type' => 'DATETIME',
                ],
                'consumed_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
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
            $this->forge->addUniqueKey('challenge_token');
            $this->forge->addKey(['user_id', 'purpose']);
            $this->forge->addKey('email');
            $this->forge->addKey('expires_at');
            $this->forge->addKey('consumed_at');
            $this->forge->createTable('mfa_otps', true);
        }

        $db = $this->db;
        if ($db->tableExists('app_settings')) {
            $existing = $db->table('app_settings')->where('setting_key', 'mfa_enabled')->get()->getRowArray();
            if (! $existing) {
                $db->table('app_settings')->insert([
                    'setting_key' => 'mfa_enabled',
                    'setting_value' => '0',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('mfa_otps')) {
            $this->forge->dropTable('mfa_otps', true);
        }

        if ($this->db->tableExists('app_settings')) {
            $this->forge->dropTable('app_settings', true);
        }
    }
}