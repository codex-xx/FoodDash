<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEstimatedPreparationTimeToOrders extends Migration
{
    public function up()
    {
        $fields = $this->db->getFieldData('orders');
        $existing = array_map(static fn($field) => $field->name, $fields);

        if (! in_array('estimated_preparation_time', $existing, true)) {
            $this->forge->addColumn('orders', [
                'estimated_preparation_time' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'status',
                ],
            ]);
        }
    }

    public function down()
    {
        $fields = $this->db->getFieldData('orders');
        $existing = array_map(static fn($field) => $field->name, $fields);

        if (in_array('estimated_preparation_time', $existing, true)) {
            $this->forge->dropColumn('orders', 'estimated_preparation_time');
        }
    }
}
