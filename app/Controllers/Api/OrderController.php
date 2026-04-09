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

class OrderController extends ResourceController
{
    protected $format = 'json';
    protected $orderModel;
    protected $orderFlow;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->orderFlow = new OrderFlowService();
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

            // Add restaurant and driver info
            $restaurantModel = new RestaurantModel();
            $driverModel = new DriverModel();

            foreach ($orders as &$order) {
                $restaurant = $restaurantModel->find($order['restaurant_id']);
                $order['restaurant'] = $restaurant;

                if ($order['driver_id']) {
                    $driver = $driverModel->find($order['driver_id']);
                    $order['driver'] = $driver ? [
                        'id'    => $driver['id'],
                        'name'  => $driver['name'],
                        'phone' => $driver['phone'],
                    ] : null;
                }
            }

            return $this->respond([
                'success' => true,
                'data'    => $orders
            ]);
        }

        $driver = $this->request->driver ?? null;

        if ($driver) {
            $orders = $this->orderModel
                ->where('driver_id', $driver['id'])
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
        $json = $this->request->getJSON();
        $input = is_object($json) ? (array) $json : $this->request->getPost();

        if (empty($input)) {
            return $this->respond(['success' => false, 'message' => 'Order payload is required'], 400);
        }

        $restaurantId = (int) ($input['restaurant_id'] ?? 0);
        $items = $input['items'] ?? null;

        if (is_string($items)) {
            $decoded = json_decode($items, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $items = $decoded;
            }
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

        // Build the data array from the received JSON object
        $sizeCategory = $this->orderFlow->getSizeCategory((float) ($input['total_amount'] ?? 0));
        $deliveryTypeId = $this->orderFlow->resolveDeliveryTypeId($sizeCategory);

        $data = [
            'order_number'     => $orderNumber,
            'customer_id'      => $customer['id'],
            'customer_name'    => $customer['name'],
            'restaurant_id'    => $restaurantId,
            'delivery_type_id' => $deliveryTypeId,
            'order_size_category' => $sizeCategory,
            'total_amount'     => (float) ($input['total_amount'] ?? 0),
            'delivery_address' => $input['delivery_address'] ?? $customer['address'],
            'items'            => json_encode($items),
            'status'           => 'pending',
            'notes'            => $input['notes'] ?? null,
        ];

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
            $unit = (float) ($itemData['price'] ?? 0);

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

    /**
     * Get single order details
     * GET /api/orders/:id
     */
    public function show($id = null)
    {
        $order = $this->orderModel->find($id);

        if (!$order) {
            return $this->respond([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
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

        if (!in_array($status, ['assigned', 'on_the_way', 'delivered'], true)) {
            return $this->respond([
                'success' => false,
                'message' => 'Invalid status for driver. Valid statuses: picked_up (or assigned), on_the_way, delivered'
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
        $orders = $this->orderModel
            ->where('driver_id', null)
            ->where('status', 'ready')
            ->orderBy('created_at', 'ASC')
            ->findAll();

        foreach ($orders as &$order) {
            $order = $this->enrichOrderForDriver($order);
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

        if ($order['driver_id']) {
            return $this->respond([
                'success' => false,
                'message' => 'Order already assigned to another driver'
            ], 400);
        }

        $result = $this->orderFlow->manualAssignDriver((int) $id, (int) $driver['id'], 'driver', (int) $driver['id']);

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

        if (!empty($order['driver_id'])) {
            $driver = $driverModel->find((int) $order['driver_id']);
            $order['driver'] = $driver ? [
                'id'    => $driver['id'],
                'name'  => $driver['name'],
                'phone' => $driver['phone'],
            ] : null;
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

        return $order;
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

        $nonCancellable = ['assigned', 'on_the_way', 'delivered'];
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
        $orderId = (int) ($this->request->getPost('order_id') ?? $this->request->getVar('order_id'));
        $status = (string) ($this->request->getPost('status') ?? $this->request->getVar('status'));

        if ($orderId <= 0 || $status === '') {
            return $this->respond([
                'success' => false,
                'message' => 'order_id and status are required'
            ], 422);
        }

        $role = (string) ($this->request->getPost('actor_role') ?? 'system');
        $actorId = (int) ($this->request->getPost('actor_id') ?? 0);

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
}
