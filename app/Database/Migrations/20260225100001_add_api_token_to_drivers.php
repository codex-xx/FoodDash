<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApiTokenToDrivers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('drivers', [
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'user_id',
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'email',
            ],
            'api_token' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'is_active',
            ],
            'fcm_token' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'api_token',
            ],
            'vehicle_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'after'      => 'phone',
            ],
            'vehicle_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'after'      => 'vehicle_type',
            ],
            'license_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'after'      => 'vehicle_number',
            ],
            'profile_image' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'license_number',
            ],
            'current_latitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,8',
                'null'       => true,
                'after'      => 'profile_image',
            ],
            'current_longitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '11,8',
                'null'       => true,
                'after'      => 'current_latitude',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('drivers', [
            'email',
            'password',
            'api_token',
            'fcm_token',
            'vehicle_type',
            'vehicle_number',
            'license_number',
            'profile_image',
            'current_latitude',
            'current_longitude',
        ]);
    }
}
