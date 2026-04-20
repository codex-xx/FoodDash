<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRestaurantCoordinates extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('latitude', 'restaurants')) {
            $fields['latitude'] = [
                'type' => 'DOUBLE',
                'null' => true,
                'after' => 'address',
            ];
        }

        if (! $this->db->fieldExists('longitude', 'restaurants')) {
            $fields['longitude'] = [
                'type' => 'DOUBLE',
                'null' => true,
                'after' => 'latitude',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('restaurants', $fields);
        }
    }

    public function down()
    {
        $drop = [];

        if ($this->db->fieldExists('latitude', 'restaurants')) {
            $drop[] = 'latitude';
        }

        if ($this->db->fieldExists('longitude', 'restaurants')) {
            $drop[] = 'longitude';
        }

        if ($drop !== []) {
            $this->forge->dropColumn('restaurants', $drop);
        }
    }
}
