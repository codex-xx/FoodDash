<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsOpenToRestaurants extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('is_open', 'restaurants')) {
            $this->forge->addColumn('restaurants', [
                'is_open' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'after'      => 'is_active',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('is_open', 'restaurants')) {
            $this->forge->dropColumn('restaurants', 'is_open');
        }
    }
}
