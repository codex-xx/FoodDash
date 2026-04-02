<?php

namespace App\Controllers\Api;

use App\Models\OrderModel;
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
        $lastId = (int) ($this->request->getGet('last_id') ?? 0);

        for ($i = 0; $i < 15; $i++) {
            $latest = $orderModel
                ->select('id, order_number, status, restaurant_id, driver_id, updated_at')
                ->where('id >', $lastId)
                ->orderBy('id', 'ASC')
                ->findAll(20);

            if (! empty($latest)) {
                $lastId = (int) end($latest)['id'];
                echo 'event: order_update' . "\n";
                echo 'data: ' . json_encode([
                    'last_id' => $lastId,
                    'orders' => $latest,
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
