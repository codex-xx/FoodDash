<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeliveryRadiusToRestaurants extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('delivery_radius_km', 'restaurants')) {
            $this->forge->addColumn('restaurants', [
                'delivery_radius_km' => [
                    'type' => 'DOUBLE',
                    'null' => true,
                    'after' => 'restaurant_longitude',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('delivery_radius_km', 'restaurants')) {
            $this->forge->dropColumn('restaurants', 'delivery_radius_km');
        }
    }
}