<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\OrderModel;
use App\Models\RestaurantModel;
use App\Models\DriverModel;
use App\Models\CustomerModel;

class OrderController extends ResourceController
{
    protected $format = 'json';
    protected $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    /**
     * Get customer orders
     * GET /api/orders
     */
    public function index()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        $customerModel = new CustomerModel();
        $customer = $customerModel->where('api_token', $token)->first();

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

        // Check if driver
        $driverModel = new DriverModel();
        $driver = $driverModel->where('api_token', $token)->first();

        if ($driver) {
            $orders = $this->orderModel
                ->where('driver_id', $driver['id'])
                ->orderBy('created_at', 'DESC')
                ->findAll();

            $restaurantModel = new RestaurantModel();
            foreach ($orders as &$order) {
                $restaurant = $restaurantModel->find($order['restaurant_id']);
                $order['restaurant'] = $restaurant;
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
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        $customerModel = new CustomerModel();
        $customer = $customerModel->where('api_token', $token)->first();

        if (!$customer) {
            return $this->respond([
                'success' => false,
                'message' => 'Only customers can create orders'
            ], 403);
        }

        // Get the JSON payload sent from the app
        $json = $this->request->getJSON();
        if (!$json) {
            return $this->respond(['success' => false, 'message' => 'Invalid JSON data received'], 400);
        }
        
        // Generate order number
        $orderNumber = 'ORD-' . strtoupper(uniqid());

        // Build the data array from the received JSON object
        $data = [
            'order_number'     => $orderNumber,
            'customer_id'      => $customer['id'],
            'customer_name'    => $customer['name'],
            'restaurant_id'    => $json->restaurant_id,
            'total_amount'     => $json->total_amount,
            'delivery_address' => $json->delivery_address ?? $customer['address'],
            'items'            => json_encode($json->items),
            'status'           => 'pending',
            'notes'            => $json->notes ?? null,
        ];

        $orderId = $this->orderModel->insert($data);

        if (!$orderId) {
            return $this->respond([
                'success' => false,
                'message' => 'Failed to create order in database'
            ], 500);
        }

        $order = $this->orderModel->find($orderId);

        return $this->respond([
            'success' => true,
            'message' => 'Order placed successfully',
            'data'    => $order
        ], 201);
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

        $restaurantModel = new RestaurantModel();
        $restaurant = $restaurantModel->find($order['restaurant_id']);
        $order['restaurant'] = $restaurant;

        if ($order['driver_id']) {
            $driverModel = new DriverModel();
            $driver = $driverModel->find($order['driver_id']);
            $order['driver'] = $driver ? [
                'id'    => $driver['id'],
                'name'  => $driver['name'],
                'phone' => $driver['phone'],
            ] : null;
        }

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
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        $driverModel = new DriverModel();
        $driver = $driverModel->where('api_token', $token)->first();

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
        
        $validStatuses = ['picked_up', 'on_the_way', 'delivered'];
        if (!in_array($status, $validStatuses)) {
            return $this->respond([
                'success' => false,
                'message' => 'Invalid status. Valid statuses: ' . implode(', ', $validStatuses)
            ], 400);
        }

        $this->orderModel->update($id, ['status' => $status]);

        return $this->respond([
            'success' => true,
            'message' => 'Order status updated',
            'data'    => $this->orderModel->find($id)
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
            ->where('status', 'ready_for_pickup')
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $restaurantModel = new RestaurantModel();
        foreach ($orders as &$order) {
            $restaurant = $restaurantModel->find($order['restaurant_id']);
            $order['restaurant'] = $restaurant;
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
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        $driverModel = new DriverModel();
        $driver = $driverModel->where('api_token', $token)->first();

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

        $this->orderModel->update($id, [
            'driver_id' => $driver['id'],
            'status'    => 'picked_up'
        ]);

        return $this->respond([
            'success' => true,
            'message' => 'Order accepted',
            'data'    => $this->orderModel->find($id)
        ]);
    }

    /**
     * Cancel order (Customer only - before pickup)
     * POST /api/orders/:id/cancel
     */
    public function cancel($id = null)
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        $customerModel = new CustomerModel();
        $customer = $customerModel->where('api_token', $token)->first();

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

        $nonCancellable = ['picked_up', 'on_the_way', 'delivered'];
        if (in_array($order['status'], $nonCancellable)) {
            return $this->respond([
                'success' => false,
                'message' => 'Cannot cancel order in current status'
            ], 400);
        }

        $this->orderModel->update($id, ['status' => 'cancelled']);

        return $this->respond([
            'success' => true,
            'message' => 'Order cancelled',
            'data'    => $this->orderModel->find($id)
        ]);
    }
}
