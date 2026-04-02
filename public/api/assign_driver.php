<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$conn = db_conn();
$payload = $_POST;

if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

$orderId = (int) ($payload['order_id'] ?? 0);
$driverId = isset($payload['driver_id']) ? (int) $payload['driver_id'] : 0;
$vehicleType = strtolower(trim((string) ($payload['vehicle_type'] ?? '')));

if ($orderId <= 0) {
    json_error('order_id is required', 422);
}

$orderStmt = $conn->prepare('SELECT id, status, total_amount, order_size_category FROM orders WHERE id = ? LIMIT 1');
if (!$orderStmt) {
    json_error('Failed to prepare order lookup', 500);
}
$orderStmt->bind_param('i', $orderId);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();
$orderStmt->close();

if (!$order) {
    json_error('Order not found', 404);
}

$sizeCategory = $order['order_size_category'] ?? get_size_category((float) ($order['total_amount'] ?? 0));
if ($vehicleType === '') {
    $vehicleType = recommended_vehicle_type($sizeCategory);
}

if ($driverId <= 0) {
    $driverStmt = $conn->prepare(
        'SELECT id FROM drivers WHERE status = ? AND is_active = 1 AND LOWER(vehicle_type) = ? ORDER BY updated_at ASC LIMIT 1'
    );
    if (!$driverStmt) {
        json_error('Failed to prepare driver query', 500);
    }

    $approved = 'approved';
    $driverStmt->bind_param('ss', $approved, $vehicleType);
    $driverStmt->execute();
    $driver = $driverStmt->get_result()->fetch_assoc();
    $driverStmt->close();

    if (!$driver) {
        json_error('No available driver matched for vehicle type: ' . $vehicleType, 404);
    }

    $driverId = (int) $driver['id'];
}

$updateStmt = $conn->prepare('UPDATE orders SET driver_id = ?, status = ?, order_size_category = ?, updated_at = NOW() WHERE id = ?');
if (!$updateStmt) {
    json_error('Failed to prepare order assignment', 500);
}
$status = 'assigned';
$updateStmt->bind_param('issi', $driverId, $status, $sizeCategory, $orderId);
if (!$updateStmt->execute()) {
    json_error('Failed to assign driver', 500);
}
$updateStmt->close();

$logStmt = $conn->prepare(
    'INSERT INTO order_status_logs (order_id, from_status, to_status, changed_by_role, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())'
);
if ($logStmt) {
    $fromStatus = normalize_order_status((string) ($order['status'] ?? 'ready'));
    $role = 'system';
    $notes = 'Driver #' . $driverId . ' assigned (' . $vehicleType . ')';
    $logStmt->bind_param('issss', $orderId, $fromStatus, $status, $role, $notes);
    $logStmt->execute();
    $logStmt->close();
}

json_success([
    'order_id' => $orderId,
    'driver_id' => $driverId,
    'vehicle_type' => $vehicleType,
    'status' => $status,
], 'Driver assigned');
