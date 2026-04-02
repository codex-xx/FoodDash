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
$status = normalize_order_status((string) ($payload['status'] ?? ''));
$actorRole = trim((string) ($payload['actor_role'] ?? 'system'));
$actorId = isset($payload['actor_id']) ? (int) $payload['actor_id'] : null;

if ($orderId <= 0 || $status === '') {
    json_error('order_id and status are required', 422);
}

if (!in_array($status, canonical_order_statuses(), true)) {
    json_error('Invalid status value', 422);
}

$stmt = $conn->prepare('SELECT id, status FROM orders WHERE id = ? LIMIT 1');
if (!$stmt) {
    json_error('Failed to prepare order lookup', 500);
}
$stmt->bind_param('i', $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    json_error('Order not found', 404);
}

$fromStatus = normalize_order_status((string) ($order['status'] ?? 'pending'));

$updateStmt = $conn->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
if (!$updateStmt) {
    json_error('Failed to prepare status update', 500);
}
$updateStmt->bind_param('si', $status, $orderId);
if (!$updateStmt->execute()) {
    json_error('Failed to update status', 500);
}
$updateStmt->close();

$logStmt = $conn->prepare(
    'INSERT INTO order_status_logs (order_id, from_status, to_status, changed_by_role, changed_by_id, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
);

if ($logStmt) {
    $notes = 'Updated via /update_status endpoint';
    $logStmt->bind_param('isssis', $orderId, $fromStatus, $status, $actorRole, $actorId, $notes);
    $logStmt->execute();
    $logStmt->close();
}

json_success([
    'order_id' => $orderId,
    'status' => $status,
], 'Order status updated');
