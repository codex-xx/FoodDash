<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLogoAndHoursToRestaurants extends Migration
{
    public function up()
    {
        $this->forge->addColumn('restaurants', [
            'logo' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'address',
            ],
            'opening_hours' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'logo',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('restaurants', ['logo', 'opening_hours']);
    }
}
