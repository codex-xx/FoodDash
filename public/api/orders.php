<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$conn = db_conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = $_POST;

    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $payload = $decoded;
        }
    }

    $restaurantId = (int) ($payload['restaurant_id'] ?? 0);
    $customerName = trim((string) ($payload['customer_name'] ?? ''));
    $totalAmount = (float) ($payload['total_amount'] ?? 0);
    $deliveryAddress = trim((string) ($payload['delivery_address'] ?? ''));
    $items = $payload['items'] ?? [];

    if ($restaurantId <= 0 || $customerName === '' || $totalAmount <= 0 || $deliveryAddress === '') {
        json_error('restaurant_id, customer_name, total_amount, and delivery_address are required', 422);
    }

    if (!is_array($items)) {
        json_error('items must be an array', 422);
    }

    $orderNumber = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));
    $sizeCategory = get_size_category($totalAmount);

    $stmt = $conn->prepare(
        'INSERT INTO orders (order_number, customer_name, restaurant_id, status, total_amount, delivery_address, items, order_size_category, created_at, updated_at) '
        . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
    );

    if (!$stmt) {
        json_error('Failed to prepare order insert', 500);
    }

    $status = 'pending';
    $itemsJson = json_encode($items);
    $stmt->bind_param('ssisdsss', $orderNumber, $customerName, $restaurantId, $status, $totalAmount, $deliveryAddress, $itemsJson, $sizeCategory);

    if (!$stmt->execute()) {
        json_error('Failed to create order', 500);
    }

    $orderId = (int) $stmt->insert_id;

    if (!empty($items)) {
        $itemStmt = $conn->prepare(
            'INSERT INTO order_items (order_id, menu_id, item_name, quantity, unit_price, line_total, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        if ($itemStmt) {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $menuId = isset($item['menu_id']) ? (int) $item['menu_id'] : null;
                $name = trim((string) ($item['name'] ?? 'Item'));
                $qty = max(1, (int) ($item['quantity'] ?? 1));
                $unitPrice = (float) ($item['price'] ?? 0);
                $lineTotal = $qty * $unitPrice;

                $itemStmt->bind_param('iisidd', $orderId, $menuId, $name, $qty, $unitPrice, $lineTotal);
                $itemStmt->execute();
            }
            $itemStmt->close();
        }
    }

    $logStmt = $conn->prepare(
        'INSERT INTO order_status_logs (order_id, from_status, to_status, changed_by_role, notes, created_at, updated_at) VALUES (?, NULL, ?, ?, ?, NOW(), NOW())'
    );

    if ($logStmt) {
        $role = 'customer';
        $note = 'Order created from API /orders endpoint';
        $logStmt->bind_param('isss', $orderId, $status, $role, $note);
        $logStmt->execute();
        $logStmt->close();
    }

    json_success([
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'status' => $status,
        'order_size_category' => $sizeCategory,
        'recommended_vehicle_type' => recommended_vehicle_type($sizeCategory),
    ], 'Order created');
}

$restaurantId = isset($_GET['restaurant_id']) ? (int) $_GET['restaurant_id'] : null;
$limit = max(1, min(100, (int) ($_GET['limit'] ?? 50)));

if ($restaurantId !== null && $restaurantId > 0) {
    $stmt = $conn->prepare(
        'SELECT id, order_number, customer_name, restaurant_id, driver_id, status, total_amount, order_size_category, created_at, updated_at '
        . 'FROM orders WHERE restaurant_id = ? ORDER BY created_at DESC LIMIT ?'
    );

    if (!$stmt) {
        json_error('Failed to prepare orders query', 500);
    }

    $stmt->bind_param('ii', $restaurantId, $limit);
} else {
    $stmt = $conn->prepare(
        'SELECT id, order_number, customer_name, restaurant_id, driver_id, status, total_amount, order_size_category, created_at, updated_at '
        . 'FROM orders ORDER BY created_at DESC LIMIT ?'
    );

    if (!$stmt) {
        json_error('Failed to prepare orders query', 500);
    }

    $stmt->bind_param('i', $limit);
}

if (!$stmt->execute()) {
    json_error('Failed to load orders', 500);
}

$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

json_success([
    'orders' => $orders,
], 'Orders fetched');
