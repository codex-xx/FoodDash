<?php

namespace App\Controllers\Api;

use App\Models\OrderModel;
use App\Models\OrderStatusLogModel;
use CodeIgniter\Controller;

class RealtimeController extends Controller
{
    public function orders()
    {
        @set_time_limit(0);

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $orderModel = new OrderModel();
        $statusLogModel = new OrderStatusLogModel();
        $lastId = (int) ($this->request->getGet('last_id') ?? 0);

        for ($i = 0; $i < 15; $i++) {
            $latestLogs = $statusLogModel
                ->select('id, order_id, to_status, changed_by_role, changed_by_id, notes, created_at')
                ->where('id >', $lastId)
                ->orderBy('id', 'ASC')
                ->findAll(20);

            if (! empty($latestLogs)) {
                $lastId = (int) end($latestLogs)['id'];
                $orderIds = array_values(array_unique(array_map(static fn(array $log): int => (int) $log['order_id'], $latestLogs)));

                $latestOrders = empty($orderIds)
                    ? []
                    : $orderModel
                        ->select('orders.id, orders.order_number, orders.status, orders.restaurant_id, orders.driver_id, orders.updated_at, d.name as driver_name, d.name as rider_name')
                        ->join('drivers d', '(d.id = orders.driver_id OR d.user_id = orders.driver_id)', 'left')
                        ->whereIn('id', $orderIds)
                        ->findAll();

                $ordersById = [];
                foreach ($latestOrders as $order) {
                    $ordersById[(int) $order['id']] = $order;
                }

                echo 'event: order_update' . "\n";
                echo 'data: ' . json_encode([
                    'last_id' => $lastId,
                    'orders' => array_values($ordersById),
                    'logs' => $latestLogs,
                ]) . "\n\n";
                @ob_flush();
                @flush();
            }

            sleep(2);
        }

        echo "event: close\n";
        echo "data: {}\n\n";
        @ob_flush();
        @flush();
    }
}
