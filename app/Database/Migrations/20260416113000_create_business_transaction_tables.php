<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBusinessTransactionTables extends Migration
{
    public function up()
    {
        $this->createProductsTable();
        $this->createTransactionsTable();
        $this->createTransactionDetailsTable();
        $this->createActivityLogsTable();
    }

    public function down()
    {
        if ($this->db->tableExists('activity_logs')) {
            $this->forge->dropTable('activity_logs', true);
        }

        if ($this->db->tableExists('transaction_details')) {
            $this->forge->dropTable('transaction_details', true);
        }

        if ($this->db->tableExists('transactions')) {
            $this->forge->dropTable('transactions', true);
        }

        if ($this->db->tableExists('products')) {
            $this->forge->dropTable('products', true);
        }
    }

    protected function createProductsTable(): void
    {
        if ($this->db->tableExists('products')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'sku' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 140,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'unit_price' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'stock_quantity' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('sku');
        $this->forge->addKey('name');
        $this->forge->addKey('is_active');
        $this->forge->createTable('products', true);
    }

    protected function createTransactionsTable(): void
    {
        if ($this->db->tableExists('transactions')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'reference_no' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['draft', 'completed', 'cancelled'],
                'default' => 'completed',
            ],
            'subtotal' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'tax_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'total_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'updated_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('reference_no');
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');
        $this->forge->createTable('transactions', true);
    }

    protected function createTransactionDetailsTable(): void
    {
        if ($this->db->tableExists('transaction_details')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'transaction_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'product_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'quantity' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'unit_price' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'line_total' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
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
        $this->forge->addKey('transaction_id');
        $this->forge->addKey('product_id');
        $this->forge->createTable('transaction_details', true);

        $this->db->query(
            'ALTER TABLE `transaction_details` ADD CONSTRAINT `fk_transaction_details_transaction` '
            . 'FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE'
        );
        $this->db->query(
            'ALTER TABLE `transaction_details` ADD CONSTRAINT `fk_transaction_details_product` '
            . 'FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT'
        );
    }

    protected function createActivityLogsTable(): void
    {
        if ($this->db->tableExists('activity_logs')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'actor_user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'entity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
            ],
            'entity_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['success', 'failed'],
                'default' => 'success',
            ],
            'message' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'context_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'ip_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('actor_user_id');
        $this->forge->addKey('action');
        $this->forge->addKey('entity_type');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');
        $this->forge->createTable('activity_logs', true);
    }
}
