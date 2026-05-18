<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRestaurantLocationProfileFields extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('restaurant_latitude', 'restaurants')) {
            $fields['restaurant_latitude'] = [
                'type' => 'DOUBLE',
                'null' => true,
                'after' => 'longitude',
            ];
        }

        if (! $this->db->fieldExists('restaurant_longitude', 'restaurants')) {
            $fields['restaurant_longitude'] = [
                'type' => 'DOUBLE',
                'null' => true,
                'after' => 'restaurant_latitude',
            ];
        }

        if (! $this->db->fieldExists('restaurant_address', 'restaurants')) {
            $fields['restaurant_address'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'address',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('restaurants', $fields);
        }

        if ($this->db->fieldExists('restaurant_address', 'restaurants')) {
            $this->db->query("UPDATE restaurants SET restaurant_address = address WHERE restaurant_address IS NULL OR restaurant_address = ''");
        }

        if ($this->db->fieldExists('restaurant_latitude', 'restaurants') && $this->db->fieldExists('latitude', 'restaurants')) {
            $this->db->query('UPDATE restaurants SET restaurant_latitude = latitude WHERE restaurant_latitude IS NULL AND latitude IS NOT NULL');
        }

        if ($this->db->fieldExists('restaurant_longitude', 'restaurants') && $this->db->fieldExists('longitude', 'restaurants')) {
            $this->db->query('UPDATE restaurants SET restaurant_longitude = longitude WHERE restaurant_longitude IS NULL AND longitude IS NOT NULL');
        }
    }

    public function down()
    {
        $drop = [];

        if ($this->db->fieldExists('restaurant_latitude', 'restaurants')) {
            $drop[] = 'restaurant_latitude';
        }

        if ($this->db->fieldExists('restaurant_longitude', 'restaurants')) {
            $drop[] = 'restaurant_longitude';
        }

        if ($this->db->fieldExists('restaurant_address', 'restaurants')) {
            $drop[] = 'restaurant_address';
        }

        if ($drop !== []) {
            $this->forge->dropColumn('restaurants', $drop);
        }
    }
}