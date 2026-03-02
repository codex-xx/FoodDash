<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddResetTokenToCustomers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('customers', [
            'reset_token' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'fcm_token',
            ],
            'reset_expires' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'reset_token',
            ],
            'reset_code' => [
                'type'       => 'VARCHAR',
                'constraint' => '6',
                'null'       => true,
                'after'      => 'reset_expires',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('customers', ['reset_token', 'reset_expires', 'reset_code']);
    }
}
