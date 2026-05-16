<?php

namespace App\Controllers;

use App\Libraries\OrderFlowService;
use App\Models\CustomerModel;
use App\Models\DriverModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\OrderStatusLogModel;
use App\Models\MenuItemModel;

class Orders extends BaseController
{
    protected $orderModel;
    protected $menuItemModel;
    protected $orderFlow;
    protected $orderItemModel;
    protected $orderStatusLogModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->menuItemModel = new MenuItemModel();
        $this->orderItemModel = new OrderItemModel();
        $this->orderStatusLogModel = new OrderStatusLogModel();
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

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('view_orders')) {
            return view('errors/unauthorized', ['message' => 'Permission denied']);
        }

        $restaurantId = $session->get('restaurant_id');

        // Orders page is for active order management only.
        $builder = $this->orderModel->builder();
        $orders = $builder
            ->select('orders.*, d.name as driver_name, d.phone as driver_phone, c.name as customer_full_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address')
            // Support both direct driver IDs and legacy mappings where order.driver_id stored drivers.user_id.
            ->join('drivers d', '(d.id = orders.driver_id OR d.user_id = orders.driver_id)', 'left')
            ->join('customers c', 'c.id = orders.customer_id', 'left')
            ->where('orders.restaurant_id', $restaurantId)
            ->whereNotIn('orders.status', ['completed', 'delivered', 'cancelled'])
            ->orderBy('orders.created_at', 'DESC')
            ->get()
            ->getResultArray();

        $orderIds = array_map(static fn(array $order): int => (int) $order['id'], $orders);
        $itemsByOrderId = [];
        if (!empty($orderIds)) {
            $rows = $this->orderItemModel
                ->whereIn('order_id', $orderIds)
                ->orderBy('id', 'ASC')
                ->findAll();

            foreach ($rows as $row) {
                $orderId = (int) ($row['order_id'] ?? 0);
                if ($orderId <= 0) {
                    continue;
                }

                $itemsByOrderId[$orderId][] = [
                    'item_name' => (string) ($row['item_name'] ?? 'Item'),
                    'quantity' => (int) ($row['quantity'] ?? 1),
                    'unit_price' => (float) ($row['unit_price'] ?? 0),
                    'line_total' => (float) ($row['line_total'] ?? 0),
                ];
            }

            $driverLogNameByOrderId = [];
            $driverLogIds = [];
            $logRows = $this->orderStatusLogModel
                ->whereIn('order_id', $orderIds)
                ->where('changed_by_role', 'driver')
                ->whereIn('to_status', ['picked_up', 'arrived_at_restaurant', 'out_for_delivery', 'delivered'])
                ->orderBy('id', 'ASC')
                ->findAll();

            foreach ($logRows as $logRow) {
                $orderId = (int) ($logRow['order_id'] ?? 0);
                $changedById = (int) ($logRow['changed_by_id'] ?? 0);

                if ($orderId <= 0 || $changedById <= 0) {
                    continue;
                }

                $driverLogIds[$changedById] = true;
                $driverLogNameByOrderId[$orderId] = $changedById;
            }

            $driverRefs = array_values(array_unique(array_filter(array_map(static function (array $order): int {
                return (int) ($order['driver_id'] ?? 0);
            }, $orders))));

            if (!empty($driverLogIds)) {
                $driverRefs = array_values(array_unique(array_merge($driverRefs, array_keys($driverLogIds))));
            }

            $driversByKey = [];
            if (!empty($driverRefs)) {
                $driverModel = new DriverModel();
                $driverRows = $driverModel
                    ->select('id, user_id, name')
                    ->groupStart()
                        ->whereIn('id', $driverRefs)
                        ->orWhereIn('user_id', $driverRefs)
                    ->groupEnd()
                    ->findAll();

                foreach ($driverRows as $driverRow) {
                    $driverName = trim((string) ($driverRow['name'] ?? ''));
                    if ($driverName === '') {
                        continue;
                    }

                    $driverId = (int) ($driverRow['id'] ?? 0);
                    $userId = (int) ($driverRow['user_id'] ?? 0);

                    if ($driverId > 0) {
                        $driversByKey[$driverId] = $driverName;
                    }

                    if ($userId > 0) {
                        $driversByKey[$userId] = $driverName;
                    }
                }
            }
        }

        foreach ($orders as &$order) {
            $id = (int) ($order['id'] ?? 0);
            $fallbackName = trim((string) ($order['customer_name'] ?? ''));
            $joinedName = trim((string) ($order['customer_full_name'] ?? ''));
            $resolvedDriverName = trim((string) ($order['driver_name'] ?? ''));
            if ($resolvedDriverName === '' && !empty($driversByKey[(int) ($order['driver_id'] ?? 0)])) {
                $resolvedDriverName = (string) $driversByKey[(int) ($order['driver_id'] ?? 0)];
            }
            if ($resolvedDriverName === '' && !empty($driverLogNameByOrderId[$id]) && !empty($driversByKey[(int) $driverLogNameByOrderId[$id]])) {
                $resolvedDriverName = (string) $driversByKey[(int) $driverLogNameByOrderId[$id]];
            }

            $order['display_customer_name'] = $joinedName !== '' ? $joinedName : ($fallbackName !== '' ? $fallbackName : 'Customer');
            $order['display_customer_phone'] = trim((string) ($order['customer_phone'] ?? ''));
            $order['display_customer_email'] = trim((string) ($order['customer_email'] ?? ''));
            $order['display_customer_address'] = trim((string) ($order['delivery_address'] ?? $order['customer_address'] ?? ''));
            $order['display_driver_name'] = $resolvedDriverName;
            $order['display_payment_type'] = $this->resolveDisplayPaymentType($order);

            $order['items_data'] = $itemsByOrderId[$id] ?? $this->parseOrderItems((string) ($order['items'] ?? ''));
        }
        unset($order);

        return view('restaurant/orders/index', ['orders' => $orders]);
    }

    private function parseOrderItems(string $items): array
    {
        if (trim($items) === '') {
            return [];
        }

        $decoded = json_decode($items, true);
        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];
        foreach ($decoded as $item) {
            $row = is_array($item) ? $item : (array) $item;
            $name = (string) ($row['item_name'] ?? $row['name'] ?? $row['title'] ?? 'Item');
            $quantity = max(1, (int) ($row['quantity'] ?? 1));
            $unitPrice = (float) ($row['unit_price'] ?? $row['price'] ?? 0);
            $lineTotal = (float) ($row['line_total'] ?? ($unitPrice * $quantity));

            $normalized[] = [
                'item_name' => $name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        return $normalized;
    }

    private function resolveDisplayPaymentType(array $order): string
    {
        $rawMethod = trim((string) ($order['payment_method'] ?? $order['payment_type'] ?? ''));
        $method = strtolower($rawMethod);
        $status = strtolower(trim((string) ($order['payment_status'] ?? '')));

        $codValues = ['cod', 'cash_on_delivery', 'cash on delivery', 'cash', '1'];
        $onlineValues = ['online', 'online_payment', 'online payment', 'gcash', 'paymaya', 'maya', 'card', 'credit_card', 'debit_card', '2'];

        if (in_array($method, $codValues, true)) {
            return 'Cash on Delivery (COD)';
        }

        if (in_array($method, $onlineValues, true)) {
            return 'Online Payment';
        }

        if ($method !== '') {
            // Any non-empty non-COD method is treated as online to avoid unknown labels.
            return 'Online Payment';
        }

        if ($status !== '') {
            if (in_array($status, ['cod', 'cash_on_delivery', 'cash on delivery', 'unpaid'], true)) {
                return 'Cash on Delivery (COD)';
            }

            if (in_array($status, ['paid', 'confirmed', 'success', 'succeeded', 'completed', 'pending', 'failed', 'cancelled', 'canceled'], true)) {
                return 'Online Payment';
            }
        }

        return '-';
    }

    /**
     * Restaurant update order status
     */
    public function updateRestaurantOrderStatus($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $role = (string) $session->get('role');
        if (!in_array($role, ['restaurant', 'admin'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('update_order_status')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
        }

        $order = $this->orderModel->find($id);
        if (!$order) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Order not found']);
        }

        if ($role === 'restaurant' && (int) $order['restaurant_id'] !== (int) $session->get('restaurant_id')) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Order not found']);
        }

        $status = $this->request->getPost('status');
        $estimatedTime = $this->request->getPost('estimated_preparation_time');
        
        $updateData = [];
        
        // Handle status update
        if ($status !== null) {
            $allowed = ['accepted', 'preparing', 'ready'];
            if (!in_array($status, $allowed)) {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Restaurant can only set: accepted, preparing, ready']);
            }
            $updateData['status'] = $status;
        }
        
        // Handle estimated preparation time update
        if ($estimatedTime !== null) {
            $updateData['estimated_preparation_time'] = (int)$estimatedTime;
        }

        if (!empty($updateData)) {
            if (array_key_exists('status', $updateData)) {
                $actorRole = $role === 'admin' ? 'admin' : 'restaurant';
                $actorId = $role === 'admin'
                    ? (int) ($session->get('user_id') ?? 0)
                    : (int) ($session->get('restaurant_id') ?? 0);

                $result = $this->orderFlow->updateStatus(
                    (int) $id,
                    (string) $updateData['status'],
                    $actorRole,
                    $actorId,
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

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_drivers')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
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

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('view_orders') && !$permissions->hasPermission('view_sales_reports')) {
            return view('errors/unauthorized', ['message' => 'Permission denied']);
        }

        $restaurantId = $session->get('restaurant_id');
        
        // Get date filters from query string
        $startDate = $this->request->getGet('start_date');
        $endDate = $this->request->getGet('end_date');
        
        $builder = $this->orderModel->builder();
        $builder
            ->select('orders.*, COALESCE(d.name, "") as rider_name')
            // Support both direct driver IDs and legacy mappings where order.driver_id stored drivers.user_id.
            ->join('drivers d', '(d.id = orders.driver_id OR d.user_id = orders.driver_id)', 'left')
            ->where('orders.restaurant_id', $restaurantId)
            // Only show delivered and cancelled orders on history page.
            ->whereIn('orders.status', ['delivered', 'cancelled']);
        
        // Apply date filtering if provided
        if (!empty($startDate)) {
            $builder->where('orders.created_at >=', $startDate . ' 00:00:00');
        }
        if (!empty($endDate)) {
            $builder->where('orders.created_at <=', $endDate . ' 23:59:59');
        }
        
        $orders = $builder->orderBy('orders.created_at', 'DESC')->get()->getResultArray();

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

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('view_sales_reports')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
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
                ->where('status', 'delivered')
            ->first()['total'] ?? 0;

        return $this->response->setJSON([
            'todayOrders' => $totalOrders,
            'todayRevenue' => $totalRevenue,
        ]);
    }
}
