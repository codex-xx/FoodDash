<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDiscountPriceToMenuItems extends Migration
{
    public function up()
    {
        $fields = [
            'discount_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'after'      => 'price'
            ],
        ];
        $this->forge->addColumn('menu_items', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('menu_items', 'discount_price');
    }
}
