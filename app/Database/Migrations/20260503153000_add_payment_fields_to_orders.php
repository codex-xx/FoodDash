<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymentFieldsToOrders extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('orders')) {
            return;
        }

        $addColumns = [];

        if (! $this->db->fieldExists('payment_method', 'orders')) {
            $addColumns['payment_method'] = [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'after'      => 'notes',
            ];
        }

        if (! $this->db->fieldExists('payment_status', 'orders')) {
            $addColumns['payment_status'] = [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'after'      => 'payment_method',
            ];
        }

        if (! $this->db->fieldExists('payment_reference', 'orders')) {
            $addColumns['payment_reference'] = [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'after'      => 'payment_status',
            ];
        }

        if (! empty($addColumns)) {
            $this->forge->addColumn('orders', $addColumns);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('orders')) {
            return;
        }

        $dropColumns = [];

        if ($this->db->fieldExists('payment_reference', 'orders')) {
            $dropColumns[] = 'payment_reference';
        }

        if ($this->db->fieldExists('payment_status', 'orders')) {
            $dropColumns[] = 'payment_status';
        }

        if ($this->db->fieldExists('payment_method', 'orders')) {
            $dropColumns[] = 'payment_method';
        }

        if (! empty($dropColumns)) {
            $this->forge->dropColumn('orders', $dropColumns);
        }
    }
}