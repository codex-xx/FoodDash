<?php

namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\MenuItemModel;

class Orders extends BaseController
{
    protected $orderModel;
    protected $menuItemModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->menuItemModel = new MenuItemModel();
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
        
        // Get date filters from query string
        $startDate = $this->request->getGet('start_date');
        $endDate = $this->request->getGet('end_date');
        
        $builder = $this->orderModel->where('restaurant_id', $restaurantId);
        
        // Apply date filtering if provided
        if (!empty($startDate)) {
            $builder->where('created_at >=', $startDate . ' 00:00:00');
        }
        if (!empty($endDate)) {
            $builder->where('created_at <=', $endDate . ' 23:59:59');
        }
        
        $orders = $builder->orderBy('created_at', 'DESC')->findAll();

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
            $allowed = ['pending', 'confirmed', 'preparing', 'ready_for_pickup', 'completed', 'cancelled'];
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
            $this->orderModel->update($id, $updateData);
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

        $driverId = $this->request->getPost('driver_id');

        $this->orderModel->update($id, ['driver_id' => $driverId, 'status' => 'assigned']);

        return $this->response->setJSON(['success' => true, 'message' => 'Driver assigned']);
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
