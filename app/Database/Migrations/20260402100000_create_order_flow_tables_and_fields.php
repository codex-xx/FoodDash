<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrderFlowTablesAndFields extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('delivery_types')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                ],
                'vehicle_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                ],
                'size_category' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('vehicle_type');
            $this->forge->createTable('delivery_types', true);

            $now = date('Y-m-d H:i:s');
            $this->db->table('delivery_types')->insertBatch([
                [
                    'name' => 'Small Order Delivery',
                    'vehicle_type' => 'motorcycle',
                    'size_category' => 'small',
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Medium Order Delivery',
                    'vehicle_type' => 'tricycle',
                    'size_category' => 'medium',
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Bulk Order Delivery',
                    'vehicle_type' => 'cab',
                    'size_category' => 'bulk',
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        if (! $this->db->tableExists('order_items')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'order_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'menu_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'item_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                ],
                'quantity' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 1,
                ],
                'unit_price' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0,
                ],
                'line_total' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('order_id');
            $this->forge->createTable('order_items', true);
        }

        if (! $this->db->tableExists('order_status_logs')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'order_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'from_status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
                'to_status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                ],
                'changed_by_role' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'null' => true,
                ],
                'changed_by_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('order_id');
            $this->forge->createTable('order_status_logs', true);
        }

        $ordersFields = $this->db->getFieldData('orders');
        $existing = array_map(static fn($field) => $field->name, $ordersFields);

        $addColumns = [];
        if (! in_array('delivery_type_id', $existing, true)) {
            $addColumns['delivery_type_id'] = [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'driver_id',
            ];
        }

        if (! in_array('order_size_category', $existing, true)) {
            $addColumns['order_size_category'] = [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'after' => 'delivery_type_id',
            ];
        }

        if (! empty($addColumns)) {
            $this->forge->addColumn('orders', $addColumns);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('order_status_logs')) {
            $this->forge->dropTable('order_status_logs', true);
        }

        if ($this->db->tableExists('order_items')) {
            $this->forge->dropTable('order_items', true);
        }

        if ($this->db->tableExists('delivery_types')) {
            $this->forge->dropTable('delivery_types', true);
        }

        $ordersFields = $this->db->getFieldData('orders');
        $existing = array_map(static fn($field) => $field->name, $ordersFields);

        $dropColumns = [];
        if (in_array('delivery_type_id', $existing, true)) {
            $dropColumns[] = 'delivery_type_id';
        }
        if (in_array('order_size_category', $existing, true)) {
            $dropColumns[] = 'order_size_category';
        }

        if (! empty($dropColumns)) {
            $this->forge->dropColumn('orders', $dropColumns);
        }
    }
}
