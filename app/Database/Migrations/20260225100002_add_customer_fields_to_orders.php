<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomerFieldsToOrders extends Migration
{
    public function up()
    {
        $this->forge->addColumn('orders', [
            'customer_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'order_number',
            ],
            'delivery_address' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'total_amount',
            ],
            'items' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'delivery_address',
            ],
            'notes' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'items',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('orders', [
            'customer_id',
            'delivery_address',
            'items',
            'notes',
        ]);
    }
}
