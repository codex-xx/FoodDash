<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveDiscountPriceFromMenuItems extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('discount_price', 'menu_items')) {
            $this->forge->dropColumn('menu_items', 'discount_price');
        }
    }

    public function down()
    {
        if (! $this->db->fieldExists('discount_price', 'menu_items')) {
            $this->forge->addColumn('menu_items', [
                'discount_price' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                    'after'      => 'price',
                ],
            ]);
        }
    }
}
