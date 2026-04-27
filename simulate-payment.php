<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.',
    ]);
    exit;
}

require __DIR__ . '/payment_flow_common.php';

function payment_json_error(string $message, int $statusCode = 400, array $errors = []): void
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'errors' => $errors,
    ]);
    exit;
}

$payload = $_POST;

if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);

    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

$userId = (int) ($payload['user_id'] ?? 0);
$orderId = (int) ($payload['order_id'] ?? 0);
$amount = (float) ($payload['amount'] ?? 0);
$paymentMethod = normalize_payment_method((string) ($payload['payment_method'] ?? ''));

$errors = [];
if ($userId <= 0) {
    $errors['user_id'] = 'user_id is required and must be a positive integer.';
}
if ($orderId <= 0) {
    $errors['order_id'] = 'order_id is required and must be a positive integer.';
}
if ($amount <= 0) {
    $errors['amount'] = 'amount is required and must be greater than 0.';
}
if ($paymentMethod === null) {
    $errors['payment_method'] = 'payment_method must be gcash or paymaya.';
}

if (!empty($errors)) {
    payment_json_error('Validation failed.', 422, $errors);
}

try {
    $conn = payment_db_conn();
    $order = fetch_order_for_payment($conn, $orderId);

    if ($order === null) {
        payment_json_error('Order not found.', 404);
    }

    $orderCustomerId = (int) ($order['customer_id'] ?? 0);
    if ($orderCustomerId > 0 && $orderCustomerId !== $userId) {
        payment_json_error('Order does not belong to the provided user_id.', 403);
    }

    $orderAmount = (float) ($order['total_amount'] ?? 0);
    if (abs($orderAmount - $amount) > 0.01) {
        payment_json_error('Amount does not match order total.', 422, [
            'expected_amount' => number_format($orderAmount, 2, '.', ''),
        ]);
    }

    $reference = trim((string) ($order['payment_reference'] ?? ''));
    if ($reference === '') {
        $reference = generate_payment_reference($paymentMethod);
    }

    $stmt = $conn->prepare(
        'UPDATE orders '
        . 'SET payment_method = ?, payment_status = ?, payment_reference = ?, updated_at = NOW() '
        . 'WHERE id = ?'
    );

    if (!$stmt) {
        payment_json_error('Failed to prepare payment update.', 500);
    }

    $pending = 'pending';
    $stmt->bind_param('sssi', $paymentMethod, $pending, $reference, $orderId);

    if (!$stmt->execute()) {
        $stmt->close();
        payment_json_error('Failed to initialize payment simulation.', 500);
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'checkout_url' => 'fake-payment-page.php?order_id=' . $orderId,
    ]);
} catch (Throwable $e) {
    payment_json_error('Unexpected error while creating payment.', 500);
}
