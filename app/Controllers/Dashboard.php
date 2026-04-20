<?php

namespace App\Controllers;

use App\Libraries\SecurityAuditService;
use App\Models\OrderModel;
use App\Models\DriverModel;
use App\Models\RestaurantModel;
use App\Models\UserModel;
use App\Models\MenuModel;
use Dompdf\Dompdf;

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

    public function adminSecurity()
    {
        $session = session();
        if (! $session->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        if ($session->get('role') !== 'admin') {
            return redirect()->to('/login')->with('error', 'Unauthorized');
        }

        return view('dashboard/admin_security');
    }

    public function adminSecurityData()
    {
        $session = session();
        if (! $session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Access denied']);
        }

        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        $tables = array_map('strtolower', $db->listTables());
        $hasAuthTokens = in_array('auth_tokens', $tables, true);
        $hasLoginActivities = in_array('login_activities', $tables, true);
        $hasUserActivities = in_array('user_activity_logs', $tables, true);
        $hasAuditLogs = in_array('audit_logs', $tables, true);
        $hasIntrusionAlerts = in_array('intrusion_alerts', $tables, true);
        $hasBlockedIps = in_array('blocked_ips', $tables, true);
        $security = new SecurityAuditService();

        $sessionStats = [
            'active_sessions' => 0,
            'active_users' => 0,
        ];

        $recentSessions = [];
        if ($hasAuthTokens) {
            $typeColumn = $db->fieldExists('user_type', 'auth_tokens') ? 'user_type' : 'actor_type';
            $userIdColumn = $db->fieldExists('user_id', 'auth_tokens') ? 'user_id' : 'actor_id';
            $idColumn = $db->fieldExists('jti', 'auth_tokens') ? 'jti' : 'jwt_id';
            $issuedColumn = $db->fieldExists('issued_at', 'auth_tokens') ? 'issued_at' : 'created_at';

            $activeSessions = $db->table('auth_tokens')
                ->where('revoked_at', null)
                ->where('expires_at >', $now)
                ->countAllResults();

            $activeUsersRows = $db->table('auth_tokens')
                ->select($typeColumn . ' as user_type, ' . $userIdColumn . ' as user_id')
                ->where('revoked_at', null)
                ->where('expires_at >', $now)
                ->groupBy($typeColumn . ', ' . $userIdColumn)
                ->get()
                ->getResultArray();

            $sessionStats['active_sessions'] = (int) $activeSessions;
            $sessionStats['active_users'] = count($activeUsersRows);

            $recentSessions = $db->table('auth_tokens')
                ->select(
                    $typeColumn . ' as user_type, '
                    . $userIdColumn . ' as user_id, '
                    . $idColumn . ' as jti, '
                    . $issuedColumn . ' as issued_at, '
                    . 'last_seen_at, expires_at, revoked_at'
                )
                ->orderBy('id', 'DESC')
                ->limit(50)
                ->get()
                ->getResultArray();
        }

        $loginAttempts = [];
        if ($hasLoginActivities) {
            $typeColumn = $db->fieldExists('user_type', 'login_activities') ? 'user_type' : 'actor_type';
            $userIdColumn = $db->fieldExists('user_id', 'login_activities') ? 'user_id' : 'actor_id';
            $successColumn = $db->fieldExists('success', 'login_activities') ? 'success' : 'login_status';
            $reasonColumn = $db->fieldExists('failure_reason', 'login_activities') ? 'failure_reason' : 'reason';
            $timeColumn = $db->fieldExists('login_at', 'login_activities') ? 'login_at' : 'created_at';

            $loginAttempts = $db->table('login_activities')
                ->select(
                    $typeColumn . ' as user_type, '
                    . $userIdColumn . ' as user_id, '
                    . $successColumn . ' as success, '
                    . $reasonColumn . ' as failure_reason, '
                    . $timeColumn . ' as login_at, '
                    . 'created_at'
                )
                ->orderBy('id', 'DESC')
                ->limit(50)
                ->get()
                ->getResultArray();

            if ($successColumn === 'login_status') {
                foreach ($loginAttempts as &$attempt) {
                    $attempt['success'] = strtolower((string) ($attempt['success'] ?? '')) === 'success' ? 1 : 0;
                }
                unset($attempt);
            }
        }

        $accountActivities = [];
        if ($hasUserActivities) {
            $accountActivities = $db->table('user_activity_logs')
                ->select('user_type, user_id, activity_type, target_type, target_id, created_at')
                ->orderBy('id', 'DESC')
                ->limit(50)
                ->get()
                ->getResultArray();
        }

        $recentAlerts = $hasIntrusionAlerts ? $security->recentAlerts(50) : [];
        $activeBlocks = $hasBlockedIps ? $security->activeBlocks(50) : [];
        $dailySummary = $security->buildReportSummary('daily');

        return $this->response->setJSON([
            'tables' => [
                'auth_tokens' => $hasAuthTokens,
                'login_activities' => $hasLoginActivities,
                'user_activity_logs' => $hasUserActivities,
                'audit_logs' => $hasAuditLogs,
                'intrusion_alerts' => $hasIntrusionAlerts,
                'blocked_ips' => $hasBlockedIps,
            ],
            'sessionStats' => $sessionStats,
            'threatStats' => [
                'failed_login_attempts' => (int) ($dailySummary['failed_login_attempts'] ?? 0),
                'intrusion_attempts' => (int) ($dailySummary['intrusion_attempts'] ?? 0),
                'blocked_ip_events' => (int) ($dailySummary['blocked_ip_events'] ?? 0),
                'system_vulnerabilities_detected' => (int) ($dailySummary['system_vulnerabilities_detected'] ?? 0),
            ],
            'recentSessions' => $recentSessions,
            'loginAttempts' => $loginAttempts,
            'accountActivities' => $accountActivities,
            'recentAlerts' => $recentAlerts,
            'activeBlocks' => $activeBlocks,
        ]);
    }

    public function adminSecurityReport()
    {
        $session = session();
        if (! $session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Access denied']);
        }

        $period = strtolower((string) $this->request->getGet('period'));
        $format = strtolower((string) $this->request->getGet('format'));
        if (! in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            $period = 'daily';
        }

        if (! in_array($format, ['json', 'csv', 'pdf'], true)) {
            $format = 'json';
        }

        $security = new SecurityAuditService();
        $summary = $security->buildReportSummary($period);

        if ($format === 'json') {
            return $this->response->setJSON($summary);
        }

        if ($format === 'csv') {
            $filename = 'security_report_' . $period . '_' . date('Ymd_His') . '.csv';
            $csv = $this->buildSecurityReportCsv($summary);

            return $this->response
                ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setBody($csv);
        }

        if (! class_exists(Dompdf::class)) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'PDF export dependency missing. Install dompdf/dompdf.',
            ]);
        }

        $dompdf = new Dompdf();
        $dompdf->loadHtml($this->buildSecurityReportHtml($summary));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'security_report_' . $period . '_' . date('Ymd_His') . '.pdf';
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    protected function buildSecurityReportCsv(array $summary): string
    {
        $rows = [
            ['metric', 'value'],
            ['period', (string) ($summary['period'] ?? '')],
            ['failed_login_attempts', (string) ($summary['failed_login_attempts'] ?? 0)],
            ['intrusion_attempts', (string) ($summary['intrusion_attempts'] ?? 0)],
            ['blocked_ip_events', (string) ($summary['blocked_ip_events'] ?? 0)],
            ['system_vulnerabilities_detected', (string) ($summary['system_vulnerabilities_detected'] ?? 0)],
            ['generated_at', (string) ($summary['generated_at'] ?? '')],
        ];

        $output = '';
        foreach ($rows as $row) {
            $escaped = array_map(static function ($value): string {
                    $value = str_replace('"', '""', (string) $value);
                    return '"' . $value . '"';
            }, $row);
            $output .= implode(',', $escaped) . "\n";
        }

        return $output;
    }

    protected function buildSecurityReportHtml(array $summary): string
    {
        $period = strtoupper((string) ($summary['period'] ?? 'daily'));
        $generatedAt = (string) ($summary['generated_at'] ?? date('Y-m-d H:i:s'));

        $safe = static function (string $value): string {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        };

        $failed = (int) ($summary['failed_login_attempts'] ?? 0);
        $intrusions = (int) ($summary['intrusion_attempts'] ?? 0);
        $blocked = (int) ($summary['blocked_ip_events'] ?? 0);
        $vuln = (int) ($summary['system_vulnerabilities_detected'] ?? 0);

        return '<html><head><style>'
            . 'body{font-family:DejaVu Sans,Arial,sans-serif;padding:24px;color:#1c1c1c;}'
            . 'h1{font-size:22px;margin:0 0 6px;}'
            . 'p{margin:0 0 18px;color:#555;}'
            . 'table{width:100%;border-collapse:collapse;}'
            . 'th,td{border:1px solid #ddd;padding:10px;text-align:left;}'
            . 'th{background:#f4f4f4;}'
            . '</style></head><body>'
            . '<h1>FoodDash Security Audit Report (' . $safe($period) . ')</h1>'
            . '<p>Generated at: ' . $safe($generatedAt) . '</p>'
            . '<table>'
            . '<tr><th>Metric</th><th>Value</th></tr>'
            . '<tr><td>Failed login attempts</td><td>' . $failed . '</td></tr>'
            . '<tr><td>Detected intrusion attempts</td><td>' . $intrusions . '</td></tr>'
            . '<tr><td>Blocked users/IP events</td><td>' . $blocked . '</td></tr>'
            . '<tr><td>System vulnerabilities detected</td><td>' . $vuln . '</td></tr>'
            . '</table>'
            . '</body></html>';
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

        // Recent orders (include rider name for order details modal)
        $recentOrders = $orderModel->builder()
            ->select('orders.id, orders.order_number, orders.customer_name, orders.restaurant_id, orders.driver_id, orders.status, orders.total_amount, orders.created_at, d.name as driver_name, d.name as rider_name')
            ->join('drivers d', '(d.id = orders.driver_id OR d.user_id = orders.driver_id)', 'left')
            ->where('orders.restaurant_id', $restaurantId)
            ->orderBy('orders.created_at', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

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

    public function restaurantLocation()
    {
        $session = session();
        if (! $session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Access denied']);
        }

        $restaurantId = (int) $session->get('restaurant_id');
        $restaurant = (new RestaurantModel())
            ->select('id, name, address, latitude, longitude')
            ->find($restaurantId);

        if (! $restaurant) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Restaurant not found']);
        }

        return $this->response->setJSON([
            'id' => (int) $restaurant['id'],
            'name' => (string) ($restaurant['name'] ?? ''),
            'address' => (string) ($restaurant['address'] ?? ''),
            'latitude' => $restaurant['latitude'] !== null ? (float) $restaurant['latitude'] : null,
            'longitude' => $restaurant['longitude'] !== null ? (float) $restaurant['longitude'] : null,
        ]);
    }

    public function updateRestaurantLocation()
    {
        $session = session();
        if (! $session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Access denied']);
        }

        $lat = $this->request->getPost('latitude');
        $lng = $this->request->getPost('longitude');

        if ($lat === null || $lng === null || ! is_numeric($lat) || ! is_numeric($lng)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Invalid coordinates']);
        }

        $restaurantId = (int) $session->get('restaurant_id');
        $ok = (new RestaurantModel())->update($restaurantId, [
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
        ]);

        if (! $ok) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Failed to update location']);
        }

        return $this->response->setJSON([
            'success' => true,
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function adminRestaurantLocations()
    {
        $session = session();
        if (! $session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Access denied']);
        }

        $rows = (new RestaurantModel())
            ->select('id, name, address, latitude, longitude')
            ->where('latitude IS NOT NULL', null, false)
            ->where('longitude IS NOT NULL', null, false)
            ->findAll();

        $restaurants = array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'name' => (string) ($row['name'] ?? ''),
                'address' => (string) ($row['address'] ?? ''),
                'latitude' => (float) $row['latitude'],
                'longitude' => (float) $row['longitude'],
            ];
        }, $rows);

        return $this->response->setJSON(['restaurants' => $restaurants]);
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
