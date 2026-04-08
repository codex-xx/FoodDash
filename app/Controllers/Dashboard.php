<?php

namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\DriverModel;
use App\Models\RestaurantModel;
use App\Models\UserModel;
use App\Models\MenuItemModel;

class Dashboard extends BaseController
{
    public function admin()
    {
        $session = session();
        if (! $session->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        if ($session->get('role') !== 'admin') {
            return redirect()->to('/login')->with('error', 'Unauthorized');
        }

        return view('dashboard/admin');
    }

    public function restaurant()
    {
        $session = session();
        if (! $session->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        if ($session->get('role') !== 'restaurant') {
            return redirect()->to('/login')->with('error', 'Unauthorized');
        }

        return view('dashboard/restaurant');
    }

    public function adminOrders()
    {
        $session = session();
        if (! $session->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        if ($session->get('role') !== 'admin') {
            return redirect()->to('/login')->with('error', 'Unauthorized');
        }

        return view('dashboard/admin_orders');
    }

    public function adminOrdersHistory()
    {
        $session = session();
        if (! $session->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        if ($session->get('role') !== 'admin') {
            return redirect()->to('/login')->with('error', 'Unauthorized');
        }

        return view('dashboard/admin_orders_history');
    }

    // Returns JSON used by admin dashboard (metrics, recent orders, chart data)
    public function adminData()
    {
        $session = session();
        if (! $session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Access denied']);
        }

        $orderModel = new OrderModel();
        $userModel = new UserModel();
        $driverModel = new DriverModel();
        $restaurantModel = new RestaurantModel();

        $todayStart = date('Y-m-d') . ' 00:00:00';
        $todayEnd   = date('Y-m-d') . ' 23:59:59';

        // Metrics
        $totalUsers = $userModel->countAllResults();
        $totalOrdersToday = $orderModel->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();
        $pendingOrders = $orderModel->where('status', 'pending')->countAllResults();
        $activeDeliveries = $orderModel->whereIn('status', ['assigned', 'on_the_way'])->countAllResults();
        $activeDrivers = $driverModel->where('is_active', 1)->countAllResults();
        $totalRestaurants = $restaurantModel->countAllResults();
        $pendingRestaurants = $restaurantModel->where('status', 'pending')->countAllResults();
        $pendingDrivers = (new DriverModel())
            ->where('status', 'pending')
            ->countAllResults();
        $pendingDriverList = (new DriverModel())
            ->select('id, name, email, phone, vehicle_type, vehicle_number, created_at')
            ->where('status', 'pending')
            ->orderBy('created_at', 'DESC')
            ->findAll(10);

        $activeDriverList = (new DriverModel())
            ->select('id, name, email, phone, vehicle_type, vehicle_number, current_latitude, current_longitude, updated_at')
            ->where('status', 'approved')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->findAll();

        $dailyRevenue = (float) $orderModel->select('IFNULL(SUM(total_amount),0) as rev')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->where('status', 'delivered')
            ->first()['rev'];

        // Recent orders with restaurant and driver names
        $builder = $orderModel->builder();
        $recent = $builder
            ->select('orders.id, order_number, customer_name, orders.restaurant_id, orders.driver_id, orders.status, orders.total_amount, orders.created_at, r.name as restaurant_name, d.name as driver_name')
            ->join('restaurants r', 'r.id = orders.restaurant_id', 'left')
            ->join('drivers d', 'd.id = orders.driver_id', 'left')
            ->orderBy('orders.created_at', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'metrics' => [
                'totalUsers' => (int) $totalUsers,
                'totalOrdersToday' => (int) $totalOrdersToday,
                'activeDeliveries' => (int) $activeDeliveries,
                'activeDrivers'    => (int) $activeDrivers,
                'totalRestaurants' => (int) $totalRestaurants,
                'dailyRevenue'     => (float) $dailyRevenue,
                'pendingOrders'    => (int) $pendingOrders,
                'pendingRestaurants' => (int) $pendingRestaurants,
                'pendingDrivers' => (int) $pendingDrivers,
            ],
            'pendingDrivers' => $pendingDriverList,
            'activeDriversList' => $activeDriverList,
            'recentOrders' => $recent,
        ]);
    }

    public function adminOrdersData()
    {
        $session = session();
        if (! $session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Access denied']);
        }

        $scope = strtolower((string) $this->request->getGet('scope'));
        $orderModel = new OrderModel();
        $driverModel = new DriverModel();
        $restaurantModel = new RestaurantModel();

        $builder = $orderModel->builder();
        $builder->select('orders.id, order_number, customer_name, orders.restaurant_id, orders.driver_id, orders.status, orders.total_amount, orders.created_at, r.name as restaurant_name, d.name as driver_name')
            ->join('restaurants r', 'r.id = orders.restaurant_id', 'left')
            ->join('drivers d', 'd.id = orders.driver_id', 'left')
            ->orderBy('orders.created_at', 'DESC');

        if ($scope === 'history') {
            $builder->whereIn('orders.status', ['delivered', 'cancelled']);
        } elseif ($scope === 'active') {
            $builder->whereNotIn('orders.status', ['delivered', 'cancelled']);
        }

        $orders = $builder->limit(100)->get()->getResultArray();

        $activeDriverList = $driverModel
            ->select('id, name, email, phone, vehicle_type, vehicle_number, current_latitude, current_longitude, updated_at')
            ->where('status', 'approved')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'orders' => $orders,
            'activeDriversList' => $activeDriverList,
        ]);
    }

    /**
     * Get restaurant dashboard data
     */
    public function restaurantData()
    {
        $session = session();
        if (! $session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Access denied']);
        }

        $restaurantId = $session->get('restaurant_id');
        $orderModel = new OrderModel();
        $menuItemModel = new MenuItemModel();

        $todayStart = date('Y-m-d') . ' 00:00:00';
        $todayEnd   = date('Y-m-d') . ' 23:59:59';

        // Today's metrics
        $todayOrders = $orderModel
            ->where('restaurant_id', $restaurantId)
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $pendingOrders = $orderModel
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'pending')
            ->countAllResults();

        $menuItems = $menuItemModel->where('restaurant_id', $restaurantId)->countAllResults();

        $dailyRevenue = (float) $orderModel
            ->select('IFNULL(SUM(total_amount),0) as rev')
            ->where('restaurant_id', $restaurantId)
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->where('status', 'delivered')
            ->first()['rev'];

        // Recent orders
        $recentOrders = $orderModel
            ->where('restaurant_id', $restaurantId)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->findAll();

        // Menu items
        $menuList = $menuItemModel
            ->where('restaurant_id', $restaurantId)
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();

        return $this->response->setJSON([
            'metrics' => [
                'todayOrders' => (int) $todayOrders,
                'pendingOrders' => (int) $pendingOrders,
                'menuItems' => (int) $menuItems,
                'dailyRevenue' => (float) $dailyRevenue,
            ],
            'recentOrders' => $recentOrders,
            'menuItems' => $menuList,
        ]);
    }

    // Update order status (AJAX)
    public function updateOrderStatus($id)
    {
        $session = session();
        if (! $session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Access denied']);
        }

        $orderModel = new OrderModel();
        $allowed = ['pending', 'accepted', 'preparing', 'ready', 'assigned', 'on_the_way', 'delivered', 'cancelled'];

        $status = $this->request->getPost('status');
        if (! in_array($status, $allowed)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid status']);
        }

        $order = $orderModel->find($id);
        if (! $order) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Order not found']);
        }

        $orderModel->update($id, ['status' => $status]);

        return $this->response->setJSON(['success' => true, 'status' => $status]);
    }
}
