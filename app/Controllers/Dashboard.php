<?php

namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\DriverModel;
use App\Models\RestaurantModel;
use App\Models\UserModel;
use App\Models\MenuModel;

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

        return redirect()->to(site_url('dashboard/admin/orders/history'));
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
        $activeDeliveries = $orderModel->whereIn('status', ['picked_up', 'arrived_at_restaurant', 'out_for_delivery'])->countAllResults();
        $activeDrivers = $driverModel->where('is_active', 1)->countAllResults();
        $totalRestaurants = $restaurantModel->countAllResults();
        $pendingRestaurants = $restaurantModel->where('status', 'pending')->countAllResults();
        $pendingDrivers = (new DriverModel())
            ->where('status', 'pending')
            ->countAllResults();
        $pendingDriverList = (new DriverModel())
            ->select('id, name, email, phone, vehicle_type, created_at')
            ->where('status', 'pending')
            ->orderBy('created_at', 'DESC')
            ->findAll(10);

        $activeDriverList = (new DriverModel())
            ->select('id, name, email, phone, vehicle_type, updated_at')
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
        $builder->select('orders.id, order_number, customer_name, orders.restaurant_id, orders.driver_id, orders.status, orders.total_amount, orders.created_at, r.name as restaurant_name, d.name as driver_name, d.name as rider_name')
            ->join('restaurants r', 'r.id = orders.restaurant_id', 'left')
            ->join('drivers d', '(d.id = orders.driver_id OR d.user_id = orders.driver_id)', 'left')
            ->orderBy('orders.created_at', 'DESC');

        if ($scope === 'history') {
            $builder->whereIn('orders.status', ['delivered', 'cancelled']);
        } elseif ($scope === 'active') {
            $builder->whereNotIn('orders.status', ['delivered', 'cancelled']);
        }

        $orders = $builder->limit(100)->get()->getResultArray();

        $activeDriverList = $driverModel
            ->select('id, name, email, phone, vehicle_type, updated_at')
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
        $menuItemModel = new MenuModel();

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

    /**
     * Get real-time admin dashboard charts data (popular menu, monthly orders, order breakdown)
     */
    public function adminChartData()
    {
        $session = session();
        if (! $session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Access denied']);
        }

        $db = \Config\Database::connect();
        $orderModel = new OrderModel();

        // Get top 5 popular menu items by delivered order count.
        // Use order_items.item_name to avoid schema mismatches across menu tables.
        $popularMenus = [];
        try {
            $popularMenus = $db->query(" 
                SELECT oi.item_name as name, COUNT(oi.id) as order_count, IFNULL(AVG(oi.unit_price), 0) as price
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.status = 'delivered'
                GROUP BY oi.item_name
                ORDER BY order_count DESC
                LIMIT 5
            ")->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'adminChartData popular menu query failed: {message}', ['message' => $e->getMessage()]);
        }

        // Get monthly orders for the current year
        $monthlyOrders = [];
        for ($month = 1; $month <= 12; $month++) {
            $startDate = date('Y') . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01 00:00:00';
            $endDate = date('Y') . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . cal_days_in_month(CAL_GREGORIAN, $month, date('Y')) . ' 23:59:59';

            $count = $orderModel
                ->where('created_at >=', $startDate)
                ->where('created_at <=', $endDate)
                ->countAllResults();

            $monthlyOrders[] = $count;
        }

        // Get order status breakdown for today
        $todayStart = date('Y-m-d') . ' 00:00:00';
        $todayEnd   = date('Y-m-d') . ' 23:59:59';

        $completed = $orderModel
            ->where('status', 'delivered')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $delivered = $orderModel
            ->whereIn('status', ['on_the_way', 'assigned'])
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $cancelled = $orderModel
            ->where('status', 'cancelled')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $pending = $orderModel
            ->where('status', 'pending')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        return $this->response->setJSON([
            'popularMenus' => $popularMenus,
            'monthlyOrders' => $monthlyOrders,
            'orderBreakdown' => [
                'completed' => $completed,
                'delivered' => $delivered,
                'cancelled' => $cancelled,
                'pending' => $pending
            ]
        ]);
    }

    /**
     * Get real-time restaurant dashboard charts data (all restaurants)
     */
    public function restaurantChartData()
    {
        $session = session();
        if (! $session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Access denied']);
        }

        $db = \Config\Database::connect();
        $orderModel = new OrderModel();

        $popularMenus = [];
        try {
            $popularMenus = $db->query(" 
                SELECT oi.item_name as name, COUNT(oi.id) as order_count, IFNULL(AVG(oi.unit_price), 0) as price
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.status = 'delivered'
                GROUP BY oi.item_name
                ORDER BY order_count DESC
                LIMIT 5
            ")->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'restaurantChartData popular menu query failed: {message}', ['message' => $e->getMessage()]);
        }

        $monthlyOrders = [];
        for ($month = 1; $month <= 12; $month++) {
            $startDate = date('Y') . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01 00:00:00';
            $endDate = date('Y') . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . cal_days_in_month(CAL_GREGORIAN, $month, date('Y')) . ' 23:59:59';

            $count = $orderModel
                ->where('created_at >=', $startDate)
                ->where('created_at <=', $endDate)
                ->countAllResults();

            $monthlyOrders[] = $count;
        }

        $todayStart = date('Y-m-d') . ' 00:00:00';
        $todayEnd   = date('Y-m-d') . ' 23:59:59';

        $completed = $orderModel
            ->where('status', 'delivered')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $delivered = $orderModel
            ->whereIn('status', ['on_the_way', 'assigned'])
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $cancelled = $orderModel
            ->where('status', 'cancelled')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $pending = $orderModel
            ->where('status', 'pending')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        return $this->response->setJSON([
            'popularMenus' => $popularMenus,
            'monthlyOrders' => $monthlyOrders,
            'orderBreakdown' => [
                'completed' => $completed,
                'delivered' => $delivered,
                'cancelled' => $cancelled,
                'pending' => $pending,
            ],
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
        $allowed = ['pending', 'accepted', 'preparing', 'ready', 'picked_up', 'arrived_at_restaurant', 'out_for_delivery', 'delivered', 'cancelled'];

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
