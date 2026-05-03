<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackfillZeroOrderTotals extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('orders') || ! $this->db->tableExists('order_items')) {
            return;
        }

        $ordersTable = $this->db->prefixTable('orders');
        $orderItemsTable = $this->db->prefixTable('order_items');

        $sql = "
            UPDATE {$ordersTable} o
            JOIN (
                SELECT order_id, ROUND(SUM(line_total), 2) AS computed_total
                FROM {$orderItemsTable}
                GROUP BY order_id
            ) i ON i.order_id = o.id
            SET o.total_amount = i.computed_total
            WHERE (o.total_amount IS NULL OR o.total_amount = 0)
              AND i.computed_total > 0
        ";

        $this->db->query($sql);
    }

    public function down()
    {
        // This is a data backfill migration and is intentionally not reversible.
    }
}