<?php

declare(strict_types=1);

function payment_db_conn(): mysqli
{
    $conn = new mysqli('localhost', 'root', '', 'fooddash_db', 3306);

    if ($conn->connect_errno) {
        throw new RuntimeException('Database connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}

function normalize_payment_method(string $method): ?string
{
    $normalized = strtolower(trim($method));

    if ($normalized === 'maya') {
        $normalized = 'paymaya';
    }

    if (!in_array($normalized, ['gcash', 'paymaya'], true)) {
        return null;
    }

    return $normalized;
}

function payment_brand_label(string $method): string
{
    return $method === 'paymaya' ? 'Maya' : 'GCash';
}

function generate_payment_reference(string $method): string
{
    $prefix = $method === 'paymaya' ? 'MYA' : 'GCS';

    return $prefix . '-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function fetch_order_for_payment(mysqli $conn, int $orderId): ?array
{
    $stmt = $conn->prepare(
        'SELECT id, customer_id, total_amount, status, payment_method, payment_status, payment_reference '
        . 'FROM orders WHERE id = ? LIMIT 1'
    );

    if (!$stmt) {
        throw new RuntimeException('Failed to prepare order query.');
    }

    $stmt->bind_param('i', $orderId);

    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Failed to load order record.');
    }

    $result = $stmt->get_result();
    $order = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $order ?: null;
}
