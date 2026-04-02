<?php

namespace App\Controllers;

use App\Libraries\OrderFlowService;
use App\Models\OrderModel;
use App\Models\MenuItemModel;

class Orders extends BaseController
{
    protected $orderModel;
    protected $menuItemModel;
    protected $orderFlow;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->menuItemModel = new MenuItemModel();
        $this->orderFlow = new OrderFlowService();
    }

    /**
     * Restaurant view orders
     */
    public function restaurantOrders()
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return redirect()->to('/login')->with('error', 'Unauthorized');
        }

        $restaurantId = $session->get('restaurant_id');

        // Orders page is for active order management only.
        $orders = $this->orderModel
            ->where('restaurant_id', $restaurantId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return view('restaurant/orders/index', ['orders' => $orders]);
    }

    /**
     * Restaurant update order status
     */
    public function updateRestaurantOrderStatus($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $order = $this->orderModel->find($id);
        if (!$order || $order['restaurant_id'] != $session->get('restaurant_id')) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Order not found']);
        }

        $status = $this->request->getPost('status');
        $estimatedTime = $this->request->getPost('estimated_preparation_time');
        
        $updateData = [];
        
        // Handle status update
        if ($status !== null) {
            $allowed = ['pending', 'accepted', 'preparing', 'ready', 'assigned', 'on_the_way', 'delivered', 'cancelled'];
            if (!in_array($status, $allowed)) {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid status']);
            }
            $updateData['status'] = $status;
        }
        
        // Handle estimated preparation time update
        if ($estimatedTime !== null) {
            $updateData['estimated_preparation_time'] = (int)$estimatedTime;
        }

        if (!empty($updateData)) {
            if (array_key_exists('status', $updateData)) {
                $result = $this->orderFlow->updateStatus(
                    (int) $id,
                    (string) $updateData['status'],
                    'restaurant',
                    (int) ($session->get('restaurant_id') ?? 0),
                    'Updated by restaurant panel'
                );

                if (!($result['ok'] ?? false)) {
                    return $this->response->setStatusCode((int) ($result['code'] ?? 400))->setJSON(['error' => $result['message']]);
                }
            }

            if (array_key_exists('estimated_preparation_time', $updateData)) {
                $this->orderModel->update($id, ['estimated_preparation_time' => $updateData['estimated_preparation_time']]);
            }
        }

        return $this->response->setJSON(['success' => true, 'status' => $status ?? $order['status']]);
    }

    /**
     * Admin assign driver to order
     */
    public function assignDriver($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $order = $this->orderModel->find($id);
        if (!$order) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Order not found']);
        }

        $driverId = (int) $this->request->getPost('driver_id');
        $result = $this->orderFlow->manualAssignDriver(
            (int) $id,
            $driverId,
            'admin',
            (int) ($session->get('user_id') ?? 0)
        );

        if (!($result['ok'] ?? false)) {
            return $this->response->setStatusCode((int) ($result['code'] ?? 400))->setJSON(['error' => $result['message']]);
        }

        return $this->response->setJSON(['success' => true, 'message' => $result['message']]);
    }

    /**
     * Restaurant view order history (completed & cancelled orders)
     */
    public function orderHistory()
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return redirect()->to('/login')->with('error', 'Unauthorized');
        }

        $restaurantId = $session->get('restaurant_id');
        
        // Get date filters from query string
        $startDate = $this->request->getGet('start_date');
        $endDate = $this->request->getGet('end_date');
        
        $builder = $this->orderModel->where('restaurant_id', $restaurantId);
        
        // Only show completed and cancelled orders
        $builder->whereIn('status', ['delivered', 'cancelled']);
        
        // Apply date filtering if provided
        if (!empty($startDate)) {
            $builder->where('created_at >=', $startDate . ' 00:00:00');
        }
        if (!empty($endDate)) {
            $builder->where('created_at <=', $endDate . ' 23:59:59');
        }
        
        $orders = $builder->orderBy('created_at', 'DESC')->findAll();

        return view('restaurant/orders/history', ['orders' => $orders]);
    }

    /**
     * Get daily sales summary for restaurant
     */
    public function getDailySales()
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $restaurantId = $session->get('restaurant_id');
        $todayStart = date('Y-m-d') . ' 00:00:00';
        $todayEnd = date('Y-m-d') . ' 23:59:59';

        $totalOrders = $this->orderModel
            ->where('restaurant_id', $restaurantId)
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $totalRevenue = (float) $this->orderModel
            ->selectSum('total_amount', 'total')
            ->where('restaurant_id', $restaurantId)
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->where('status', 'completed')
            ->first()['total'] ?? 0;

        return $this->response->setJSON([
            'todayOrders' => $totalOrders,
            'todayRevenue' => $totalRevenue,
        ]);
    }
}
