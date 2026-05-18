<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\OrderFlowService;
use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\RestaurantModel;
use App\Models\DriverModel;
use App\Models\CustomerModel;
use App\Models\MenuModel;
use App\Libraries\PermissionService;

class OrderController extends ResourceController
{
    protected $format = 'json';
    protected $orderModel;
    protected $orderFlow;
    protected $permissions;
    private ?array $ordersTableColumns = null;
    private const RIDER_CONFIRMED_STATUSES = ['picked_up', 'arrived_at_restaurant', 'out_for_delivery', 'delivered'];

    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->orderFlow = new OrderFlowService();
        $this->permissions = new PermissionService();
    }

    /**
     * Get customer orders
     * GET /api/orders
     */
    public function index()
    {
        $customer = $this->request->customer ?? null;

        if ($customer) {
            $orders = $this->orderModel
                ->where('customer_id', $customer['id'])
                ->orderBy('created_at', 'DESC')
                ->findAll();

            foreach ($orders as &$order) {
                $order = $this->enrichOrderForDriver($order);
            }

            return $this->respond([
                'success' => true,
                'data'    => $orders
            ]);
        }

        $driver = $this->request->driver ?? null;

        if ($driver) {
            $assignedToDriver = $this->orderModel
                ->where('driver_id', (int) $driver['id'])
                ->whereIn('status', ['accepted', 'preparing', 'ready', 'picked_up', 'arrived_at_restaurant', 'out_for_delivery'])
                ->orderBy('created_at', 'DESC')
                ->findAll();

            $openIncoming = $this->orderModel
                ->where('driver_id', null)
                ->whereIn('status', ['accepted', 'preparing', 'ready'])
                ->orderBy('created_at', 'DESC')
                ->findAll();

            $ordersById = [];
            foreach (array_merge($assignedToDriver, $openIncoming) as $order) {
                $ordersById[(int) $order['id']] = $order;
            }
            $orders = array_values($ordersById);

            foreach ($orders as &$order) {
                $order = $this->enrichOrderForDriver($order);
            }

            return $this->respond([
                'success' => true,
                'data'    => $orders
            ]);
        }

        return $this->respond([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    /**
     * Create new order (Customer only)
     * POST /api/orders
     */
    public function create()
    {
        $customer = $this->request->customer ?? null;

        if (!$customer) {
            return $this->respond([
                'success' => false,
                'message' => 'Only customers can create orders'
            ], 403);
        }

        // Accept both JSON and form payloads for mobile compatibility.
        $input = [];
        $contentType = strtolower((string) $this->request->getHeaderLine('Content-Type'));
        if (str_contains($contentType, 'application/json')) {
            $raw = (string) $this->request->getBody();
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $input = $decoded;
                }
            }
        }

        if (empty($input)) {
            $input = $this->request->getPost();
        }

        if (empty($input)) {
            return $this->respond(['success' => false, 'message' => 'Order payload is required'], 400);
        }

        if (isset($input['order']) && is_array($input['order'])) {
            $input = array_merge($input['order'], $input);
        }

        // Normalize legacy and camelCase keys sent by older Android clients.
        $restaurantId = (int) (
            $input['restaurant_id']
            ?? $input['restaurantId']
            ?? $input['restaurantID']
            ?? $input['resto_id']
            ?? $input['store_id']
            ?? $input['shop_id']
            ?? $input['vendor_id']
            ?? 0
        );

        $items = $input['items']
            ?? $input['order_items']
            ?? $input['orderItems']
            ?? $input['cart_items']
            ?? $input['cartItems']
            ?? $input['cart']
            ?? $input['menu_items']
            ?? $input['menuItems']
            ?? $input['products']
            ?? $input['product_items']
            ?? $input['food_items']
            ?? $input['foods']
            ?? $input['selected_items']
            ?? $input['line_items']
            ?? null;

        if (is_string($items)) {
            $decoded = json_decode($items, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $items = $decoded;
            }
        }

        if (is_object($items)) {
            $items = (array) $items;
        }

        if (is_array($items) && !$this->isListArray($items)) {
            // Some clients submit a single item object instead of an array.
            if ($this->looksLikeOrderItem($items)) {
                $items = [$items];
            } elseif (isset($items['items']) && is_array($items['items'])) {
                $items = $items['items'];
            }
        }

        if ($restaurantId <= 0 && is_array($items) && !empty($items)) {
            $first = is_array($items[0] ?? null) ? $items[0] : [];
            $restaurantId = (int) (
                $first['restaurant_id']
                ?? $first['restaurantId']
                ?? $first['store_id']
                ?? 0
            );
        }

        if ($restaurantId <= 0 || !is_array($items) || empty($items)) {
            return $this->respond([
                'success' => false,
                'message' => 'restaurant_id and a non-empty items array are required'
            ], 422);
        }

        $validation = $this->validateOrderItemsAvailability($restaurantId, $items);
        if (!empty($validation['invalid_items'])) {
            return $this->respond([
                'success' => false,
                'message' => 'Some items are not available for a moment. Please refresh menu.',
                'notification' => 'Not available for a moment',
                'invalid_items' => $validation['invalid_items'],
            ], 409);
        }
        
        // Generate order number
        $orderNumber = 'ORD-' . strtoupper(uniqid());

        // Build the data array from the received JSON object.
        $totalAmount = $this->resolveOrderTotalAmount($input, $items);
        $sizeCategory = $this->orderFlow->getSizeCategory($totalAmount);
        $deliveryTypeId = $this->orderFlow->resolveDeliveryTypeId($sizeCategory);
        $deliveryAddress = $input['delivery_address'] ?? $input['deliveryAddress'] ?? $customer['address'];
        $paymentMethod = $input['payment_method'] ?? $input['paymentMethod'] ?? $input['payment_type'] ?? $input['paymentType'] ?? null;
        $paymentStatus = $input['payment_status'] ?? $input['paymentStatus'] ?? null;
        $paymentReference = $input['payment_reference'] ?? $input['paymentReference'] ?? null;

        $data = [
            'order_number'     => $orderNumber,
            'customer_id'      => $customer['id'],
            'customer_name'    => $customer['name'],
            'restaurant_id'    => $restaurantId,
            'delivery_type_id' => $deliveryTypeId,
            'order_size_category' => $sizeCategory,
            'total_amount'     => $totalAmount,
            'delivery_address' => $deliveryAddress,
            'items'            => json_encode($items),
            'status'           => 'pending',
            'notes'            => $input['notes'] ?? $input['special_instructions'] ?? null,
        ];

        // Keep checkout backward compatible with databases that have not migrated payment columns yet.
        if ($this->ordersTableHasColumn('payment_method')) {
            $data['payment_method'] = $paymentMethod;
        }

        if ($this->ordersTableHasColumn('payment_status')) {
            $data['payment_status'] = $paymentStatus;
        }

        if ($this->ordersTableHasColumn('payment_reference')) {
            $data['payment_reference'] = $paymentReference;
        }

        $orderId = $this->orderModel->insert($data);

        if (!$orderId) {
            return $this->respond([
                'success' => false,
                'message' => 'Failed to create order in database'
            ], 500);
        }

        $orderItemModel = new OrderItemModel();
        foreach ($items as $item) {
            $itemData = is_object($item) ? (array) $item : (array) $item;
            $qty = max(1, (int) ($itemData['quantity'] ?? 1));
            $unit = $this->normalizeMoney(
                $itemData['price']
                ?? $itemData['unit_price']
                ?? $itemData['unitPrice']
                ?? 0
            );

            $orderItemModel->insert([
                'order_id' => $orderId,
                'menu_id' => $this->extractItemId($itemData),
                'item_name' => $this->extractItemName($itemData) ?? 'Item',
                'quantity' => $qty,
                'unit_price' => $unit,
                'line_total' => $qty * $unit,
            ]);
        }

        $order = $this->orderModel->find($orderId);

        return $this->respond([
            'success' => true,
            'message' => 'Order placed successfully',
            'recommended_vehicle_type' => $this->orderFlow->getVehicleTypeForSize($sizeCategory),
            'data'    => $order
        ], 201);
    }

    private function validateOrderItemsAvailability(int $restaurantId, array $items): array
    {
        $menuModel = new MenuModel();
        $invalidItems = [];

        foreach ($items as $item) {
            $itemArray = is_object($item) ? (array) $item : (array) $item;

            $itemId = $this->extractItemId($itemArray);
            $itemName = $this->extractItemName($itemArray);

            if ($itemId !== null) {
                $menu = $menuModel
                    ->where('id', $itemId)
                    ->where('restaurant_id', $restaurantId)
                    ->first();
            } elseif ($itemName !== null) {
                $menu = $menuModel
                    ->where('restaurant_id', $restaurantId)
                    ->where('name', $itemName)
                    ->first();
            } else {
                $invalidItems[] = [
                    'item' => $itemArray,
                    'reason' => 'Item identifier is missing',
                    'can_order' => false,
                    'ui_disabled' => true,
                    'availability_message' => 'Not available for a moment',
                ];
                continue;
            }

            if (!$menu) {
                $invalidItems[] = [
                    'item_id' => $itemId,
                    'name' => $itemName,
                    'reason' => 'Menu item not found',
                    'can_order' => false,
                    'ui_disabled' => true,
                    'availability_message' => 'Not available for a moment',
                ];
                continue;
            }

            if ((int) ($menu['availability'] ?? 1) !== 1) {
                $invalidItems[] = [
                    'item_id' => (int) $menu['id'],
                    'name' => $menu['name'],
                    'reason' => 'Item is unavailable',
                    'can_order' => false,
                    'ui_disabled' => true,
                    'availability_message' => 'Not available for a moment',
                ];
            }
        }

        return ['invalid_items' => $invalidItems];
    }

    private function extractItemId(array $item): ?int
    {
        foreach (['menu_id', 'item_id', 'product_id', 'id'] as $key) {
            if (isset($item[$key]) && is_numeric($item[$key])) {
                $id = (int) $item[$key];
                return $id > 0 ? $id : null;
            }
        }

        return null;
    }

    private function extractItemName(array $item): ?string
    {
        foreach (['name', 'item_name', 'product_name', 'title'] as $key) {
            if (isset($item[$key])) {
                $name = trim((string) $item[$key]);
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return null;
    }

    private function isListArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    private function looksLikeOrderItem(array $item): bool
    {
        foreach (['menu_id', 'item_id', 'product_id', 'id', 'name', 'item_name', 'product_name', 'title'] as $key) {
            if (array_key_exists($key, $item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get single order details
     * GET /api/orders/:id
     */
    public function show($id = null)
    {
        $actor = $this->resolveActor();
        if (! $this->permissions->allows($actor['role'], 'orders', 'read')) {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $order = $this->orderModel->find($id);

        if (!$order) {
            return $this->respond([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if (! $this->canAccessOrder($actor, $order)) {
            return $this->respond([
                'success' => false,
                'message' => 'You are not allowed to access this order'
            ], 403);
        }

        $order = $this->enrichOrderForDriver($order);

        return $this->respond([
            'success' => true,
            'data'    => $order
        ]);
    }

    /**
    * Update order status (Driver only)
     * PUT /api/orders/:id/status
     */
    public function updateStatus($id = null)
    {
        $driver = $this->request->driver ?? null;

        if (!$driver) {
            return $this->respond([
                'success' => false,
                'message' => 'Only drivers can update order status'
            ], 403);
        }

        $order = $this->orderModel->find($id);

        if (!$order) {
            return $this->respond([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $status = $this->request->getJSON()->status ?? $this->request->getPost('status');
        $status = $this->orderFlow->normalizeStatus((string) $status);

        if (!in_array($status, ['picked_up', 'arrived_at_restaurant', 'out_for_delivery', 'delivered'], true)) {
            return $this->respond([
                'success' => false,
                'message' => 'Invalid status for driver. Valid statuses: picked_up, arrived_at_restaurant, out_for_delivery, delivered'
            ], 400);
        }

        $result = $this->orderFlow->updateStatus((int) $id, $status, 'driver', (int) $driver['id'], 'Driver app status update');
        if (!($result['ok'] ?? false)) {
            return $this->respond([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to update status'
            ], (int) ($result['code'] ?? 400));
        }

        return $this->respond([
            'success' => true,
            'message' => 'Order status updated',
            'data'    => $result['order']
        ]);
    }

    /**
     * Get available orders for drivers
     * GET /api/orders/available
     */
    public function available()
    {
        $actor = $this->resolveActor();

        if ($actor['role'] !== 'driver' || ! $this->permissions->allows('driver', 'orders', 'read')) {
            return $this->respond([
                'success' => false,
                'message' => 'Only drivers can view available orders'
            ], 403);
        }

        $incomingAssignedOrders = $this->orderModel
            ->where('driver_id', (int) $actor['id'])
            ->whereIn('status', ['accepted', 'preparing', 'ready'])
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $openPoolOrders = $this->orderModel
            ->where('driver_id', null)
            ->whereIn('status', ['accepted', 'preparing', 'ready'])
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $ordersById = [];
        foreach (array_merge($incomingAssignedOrders, $openPoolOrders) as $order) {
            $ordersById[(int) $order['id']] = $order;
        }

        $orders = [];
        $driver = $this->request->driver ?? null;

        foreach (array_values($ordersById) as $order) {
            $order = $this->enrichOrderForDriver($order);

            if (empty($order['driver_id']) && $driver) {
                $eligibility = $this->orderFlow->canDriverAcceptRestaurantOrder($driver, $order['restaurant'] ?? []);

                if (($eligibility['location_ready'] ?? false) && ! ($eligibility['allowed'] ?? false)) {
                    continue;
                }

                if (isset($eligibility['distance_km']) && $eligibility['distance_km'] !== null) {
                    $order['distance_to_restaurant_km'] = round((float) $eligibility['distance_km'], 2);
                    $order['delivery_radius_km'] = $eligibility['radius_km'] ?? null;
                }
            }

            $orders[] = $order;
        }

        return $this->respond([
            'success' => true,
            'data'    => $orders
        ]);
    }

    /**
     * Accept order (Driver)
     * POST /api/orders/:id/accept
     */
    public function accept($id = null)
    {
        $driver = $this->request->driver ?? null;

        if (!$driver) {
            return $this->respond([
                'success' => false,
                'message' => 'Only drivers can accept orders'
            ], 403);
        }

        $order = $this->orderModel->find($id);

        if (!$order) {
            return $this->respond([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if (!empty($order['driver_id']) && (int) $order['driver_id'] !== (int) $driver['id']) {
            return $this->respond([
                'success' => false,
                'message' => 'Order already assigned to another driver'
            ], 400);
        }

        if (in_array((string) $order['status'], ['out_for_delivery', 'delivered', 'cancelled'], true)) {
            return $this->respond([
                'success' => false,
                'message' => 'Order cannot be accepted in current status'
            ], 400);
        }

        if ((string) $order['status'] === 'accepted' && (int) ($order['driver_id'] ?? 0) === (int) $driver['id']) {
            return $this->respond([
                'success' => true,
                'message' => 'Order already accepted',
                'data'    => $this->enrichOrderForDriver($order)
            ]);
        }

        $result = $this->orderFlow->acceptOrder((int) $id, (int) $driver['id'], 'Driver accepted incoming request');

        if (!($result['ok'] ?? false)) {
            return $this->respond([
                'success' => false,
                'message' => $result['message'] ?? 'Unable to assign order'
            ], (int) ($result['code'] ?? 400));
        }

        return $this->respond([
            'success' => true,
            'message' => 'Order accepted',
            'data'    => $this->enrichOrderForDriver($result['order'])
        ]);
    }

    /**
     * Fetch nearby eligible riders for a restaurant or order.
     * GET /api/restaurants/:id/nearby-riders
     * GET /api/orders/:id/nearby-riders
     */
    public function nearbyRiders($id = null, string $source = 'restaurant')
    {
        $actor = $this->resolveActor();

        if (! in_array($actor['role'], ['restaurant', 'admin'], true)) {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $restaurantId = (int) $id;
        if ($source === 'order') {
            $order = $this->orderModel->find($restaurantId);
            if (! $order) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            if ($actor['role'] === 'restaurant' && (int) ($order['restaurant_id'] ?? 0) !== (int) ($actor['restaurant_id'] ?? 0)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'You are not allowed to access this order'
                ], 403);
            }

            $restaurantId = (int) ($order['restaurant_id'] ?? 0);
        } elseif ($actor['role'] === 'restaurant' && $restaurantId !== (int) ($actor['restaurant_id'] ?? 0)) {
            return $this->respond([
                'success' => false,
                'message' => 'You are not allowed to access this restaurant'
            ], 403);
        }

        if ($restaurantId <= 0) {
            return $this->respond([
                'success' => false,
                'message' => 'restaurant_id is required'
            ], 422);
        }

        $limit = (int) ($this->request->getGet('limit') ?? 20);
        $result = $this->orderFlow->getNearbyEligibleDriversForRestaurant($restaurantId, $limit > 0 ? $limit : 20);

        if (! ($result['ok'] ?? false)) {
            return $this->respond([
                'success' => false,
                'message' => $result['message'] ?? 'Unable to fetch nearby riders'
            ], (int) ($result['code'] ?? 400));
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'restaurant' => $result['restaurant'],
                'delivery_radius_km' => $result['delivery_radius_km'],
                'nearby_riders' => $result['nearby_riders'],
            ],
        ]);
    }

    private function enrichOrderForDriver(array $order): array
    {
        $restaurantModel = new RestaurantModel();
        $customerModel = new CustomerModel();
        $driverModel = new DriverModel();
        $orderItemModel = new OrderItemModel();

        $restaurant = $restaurantModel->find($order['restaurant_id']);
        $order['restaurant'] = $restaurant;

        $customer = null;
        if (!empty($order['customer_id'])) {
            $customer = $customerModel
                ->select('id, name, email, phone, address')
                ->find((int) $order['customer_id']);
        }

        $order['customer'] = $customer ? [
            'id' => $customer['id'],
            'name' => $customer['name'] ?? ($order['customer_name'] ?? null),
            'email' => $customer['email'] ?? null,
            'phone' => $customer['phone'] ?? null,
            'address' => $customer['address'] ?? ($order['delivery_address'] ?? null),
        ] : [
            'id' => $order['customer_id'] ?? null,
            'name' => $order['customer_name'] ?? null,
            'phone' => null,
            'address' => $order['delivery_address'] ?? null,
        ];

        if (!empty($order['driver_id']) && $this->canExposeDriverToCustomer($order)) {
            $driver = $driverModel->find((int) $order['driver_id']);
            $order['driver'] = $driver ? [
                'id'             => $driver['id'],
                'name'           => $driver['name'],
                'phone'          => $driver['phone'],
                'vehicle_type'    => $driver['vehicle_type'] ?? null,
                'vehicle_number'  => $driver['vehicle_number'] ?? null,
                'vehicle'        => trim((string) (($driver['vehicle_type'] ?? '') . ' ' . ($driver['vehicle_number'] ?? ''))),
            ] : null;
        } else {
            $order['driver'] = null;
        }

        $orderItems = $orderItemModel
            ->where('order_id', (int) $order['id'])
            ->findAll();

        if (!empty($orderItems)) {
            $order['items_data'] = $orderItems;
        } else {
            $order['items_data'] = $this->parseOrderItems($order['items'] ?? null);
        }

        $order['customer_phone'] = $order['customer']['phone'] ?? null;
        $order['customer_address'] = $order['customer']['address'] ?? ($order['delivery_address'] ?? null);
        $order['payment_method_label'] = $this->resolvePaymentMethodLabel($order);
        $order['display_payment_method'] = $order['payment_method_label'];

        return $order;
    }

    private function resolvePaymentMethodLabel(array $order): string
    {
        $rawMethod = trim((string) ($order['payment_method'] ?? $order['payment_type'] ?? ''));
        $normalizedMethod = strtolower($rawMethod);
        $status = strtolower(trim((string) ($order['payment_status'] ?? '')));

        if ($normalizedMethod !== '') {
            if (in_array($normalizedMethod, ['cod', 'cash_on_delivery', 'cash on delivery', 'cash'], true)) {
                return 'Cash on Delivery';
            }

            return ucwords(str_replace(['_', '-'], ' ', $rawMethod));
        }

        if (in_array($status, ['cod', 'cash_on_delivery', 'cash on delivery', 'unpaid'], true)) {
            return 'Cash on Delivery';
        }

        if ($status !== '') {
            return ucwords(str_replace(['_', '-'], ' ', $status));
        }

        return '-';
    }

    private function resolveOrderTotalAmount(array $input, array $items): float
    {
        $explicitTotal = $this->normalizeMoney(
            $input['total_amount']
            ?? $input['totalAmount']
            ?? $input['order_total']
            ?? $input['orderTotal']
            ?? $input['grand_total']
            ?? $input['grandTotal']
            ?? $input['amount']
            ?? $input['payment_amount']
            ?? $input['paymentAmount']
            ?? 0
        );

        if ($explicitTotal > 0) {
            return $explicitTotal;
        }

        $itemsTotal = 0.0;
        foreach ($items as $item) {
            $row = is_object($item) ? (array) $item : (array) $item;
            $qty = max(1, (int) ($row['quantity'] ?? 1));

            $lineTotal = $this->normalizeMoney($row['line_total'] ?? $row['lineTotal'] ?? 0);
            if ($lineTotal > 0) {
                $itemsTotal += $lineTotal;
                continue;
            }

            $unitPrice = $this->normalizeMoney($row['unit_price'] ?? $row['unitPrice'] ?? $row['price'] ?? 0);
            $itemsTotal += ($unitPrice * $qty);
        }

        $feeTotal =
            $this->normalizeMoney($input['delivery_fee'] ?? $input['deliveryFee'] ?? 0)
            + $this->normalizeMoney($input['shipping_fee'] ?? $input['shippingFee'] ?? 0)
            + $this->normalizeMoney($input['service_fee'] ?? $input['serviceFee'] ?? 0)
            + $this->normalizeMoney($input['platform_fee'] ?? $input['platformFee'] ?? 0)
            + $this->normalizeMoney($input['tax'] ?? $input['tax_amount'] ?? $input['taxAmount'] ?? 0);

        return round(max(0, $itemsTotal + $feeTotal), 2);
    }

    private function normalizeMoney($value): float
    {
        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        if (is_string($value)) {
            $normalized = preg_replace('/[^0-9.,-]/', '', $value);
            if ($normalized === null || $normalized === '') {
                return 0.0;
            }

            // Handle formats like 1,234.56 or 1234,56.
            if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
                $normalized = str_replace(',', '', $normalized);
            } elseif (str_contains($normalized, ',')) {
                $normalized = str_replace(',', '.', $normalized);
            }

            return round((float) $normalized, 2);
        }

        return 0.0;
    }

    private function parseOrderItems($items): array
    {
        if (is_array($items)) {
            return $items;
        }

        if (!is_string($items) || trim($items) === '') {
            return [];
        }

        $decoded = json_decode($items, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function ordersTableHasColumn(string $column): bool
    {
        if ($this->ordersTableColumns === null) {
            $db = \Config\Database::connect();
            $fieldNames = $db->getFieldNames('orders');
            $this->ordersTableColumns = array_fill_keys($fieldNames, true);
        }

        return !empty($this->ordersTableColumns[$column]);
    }

    private function canExposeDriverToCustomer(array $order): bool
    {
        $status = $this->orderFlow->normalizeStatus((string) ($order['status'] ?? ''));

        return in_array($status, self::RIDER_CONFIRMED_STATUSES, true);
    }

    /**
     * Cancel order (Customer only - before pickup)
     * POST /api/orders/:id/cancel
     */
    public function cancel($id = null)
    {
        $customer = $this->request->customer ?? null;

        if (!$customer) {
            return $this->respond([
                'success' => false,
                'message' => 'Only customers can cancel orders'
            ], 403);
        }

        $order = $this->orderModel->find($id);

        if (!$order) {
            return $this->respond([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if ($order['customer_id'] != $customer['id']) {
            return $this->respond([
                'success' => false,
                'message' => 'You can only cancel your own orders'
            ], 403);
        }

        $nonCancellable = ['picked_up', 'arrived_at_restaurant', 'out_for_delivery', 'delivered'];
        if (in_array($order['status'], $nonCancellable)) {
            return $this->respond([
                'success' => false,
                'message' => 'Cannot cancel order in current status'
            ], 400);
        }

        $result = $this->orderFlow->updateStatus((int) $id, 'cancelled', 'customer', (int) $customer['id'], 'Customer cancelled order');
        if (!($result['ok'] ?? false)) {
            return $this->respond([
                'success' => false,
                'message' => $result['message'] ?? 'Unable to cancel order'
            ], (int) ($result['code'] ?? 400));
        }

        return $this->respond([
            'success' => true,
            'message' => 'Order cancelled',
            'data'    => $result['order']
        ]);
    }

    public function updateStatusEndpoint()
    {
        $driver = $this->request->driver ?? null;
        $session = session();
        $sessionRole = (string) $session->get('role');

        $isAdminSession = (bool) $session->get('isLoggedIn')
            && $sessionRole === 'admin'
            && $this->permissions->allows($sessionRole, 'orders', 'update');

        if (!$isAdminSession && !$driver) {
            return $this->respond([
                'success' => false,
                'message' => 'Driver or admin authorization required'
            ], 403);
        }

        $orderId = (int) (
            $this->request->getPost('order_id')
            ?? $this->request->getVar('order_id')
            ?? $this->request->getPost('orderId')
            ?? $this->request->getVar('orderId')
            ?? $this->request->getPost('id')
            ?? $this->request->getVar('id')
        );

        $status = (string) (
            $this->request->getPost('status')
            ?? $this->request->getVar('status')
            ?? $this->request->getPost('order_status')
            ?? $this->request->getVar('order_status')
            ?? $this->request->getPost('orderStatus')
            ?? $this->request->getVar('orderStatus')
        );

        if ($orderId <= 0 || $status === '') {
            return $this->respond([
                'success' => false,
                'message' => 'order_id and status are required'
            ], 422);
        }

        $role = $driver ? 'driver' : (string) ($this->request->getPost('actor_role') ?? 'admin');
        $actorId = $driver
            ? (int) ($driver['id'] ?? 0)
            : (int) ($this->request->getPost('actor_id') ?? 0);

        $result = $this->orderFlow->updateStatus($orderId, $status, $role, $actorId > 0 ? $actorId : null, 'Public update_status endpoint');

        if (!($result['ok'] ?? false)) {
            return $this->respond([
                'success' => false,
                'message' => $result['message'] ?? 'Unable to update status'
            ], (int) ($result['code'] ?? 400));
        }

        return $this->respond([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['order']
        ]);
    }

    public function assignDriverEndpoint()
    {
        $session = session();
        $role = (string) $session->get('role');

        if (! (bool) $session->get('isLoggedIn') || $role !== 'admin' || ! $this->permissions->allows($role, 'orders', 'assign')) {
            return $this->respond([
                'success' => false,
                'message' => 'Admin authorization required'
            ], 403);
        }

        $orderId = (int) ($this->request->getPost('order_id') ?? $this->request->getVar('order_id'));
        $driverId = (int) ($this->request->getPost('driver_id') ?? $this->request->getVar('driver_id'));

        if ($orderId <= 0 || $driverId <= 0) {
            return $this->respond([
                'success' => false,
                'message' => 'order_id and driver_id are required'
            ], 422);
        }

        $result = $this->orderFlow->manualAssignDriver($orderId, $driverId, 'admin', null);
        if (!($result['ok'] ?? false)) {
            return $this->respond([
                'success' => false,
                'message' => $result['message'] ?? 'Unable to assign driver'
            ], (int) ($result['code'] ?? 400));
        }

        return $this->respond([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['order']
        ]);
    }

    private function resolveActor(): array
    {
        $customer = $this->request->customer ?? null;
        if ($customer) {
            return ['role' => 'customer', 'id' => (int) $customer['id']];
        }

        $driver = $this->request->driver ?? null;
        if ($driver) {
            return ['role' => 'driver', 'id' => (int) $driver['id']];
        }

        $session = session();
        if ((bool) $session->get('isLoggedIn')) {
            return [
                'role' => (string) $session->get('role'),
                'id' => (int) $session->get('user_id'),
                'restaurant_id' => (int) $session->get('restaurant_id'),
            ];
        }

        return ['role' => null, 'id' => 0];
    }

    private function canAccessOrder(array $actor, array $order): bool
    {
        $role = $actor['role'] ?? null;

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'customer') {
            return (int) ($order['customer_id'] ?? 0) === (int) ($actor['id'] ?? 0);
        }

        if ($role === 'driver') {
            return (int) ($order['driver_id'] ?? 0) === (int) ($actor['id'] ?? 0);
        }

        if ($role === 'restaurant') {
            return (int) ($order['restaurant_id'] ?? 0) === (int) ($actor['restaurant_id'] ?? 0);
        }

        return false;
    }
}
