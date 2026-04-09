<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMfaAndJwtFields extends Migration
{
    public function up()
    {
        $columns = [
            'mfa_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'login_otp_code' => [
                'type' => 'VARCHAR',
                'constraint' => '6',
                'null' => true,
            ],
            'login_otp_expires' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'token_version' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
        ];

        $this->forge->addColumn('customers', $columns);
        $this->forge->addColumn('drivers', $columns);
        $this->forge->addColumn('users', $columns);
    }

    public function down()
    {
        $drop = ['mfa_enabled', 'login_otp_code', 'login_otp_expires', 'token_version'];

        $this->forge->dropColumn('customers', $drop);
        $this->forge->dropColumn('drivers', $drop);
        $this->forge->dropColumn('users', $drop);
    }
}
