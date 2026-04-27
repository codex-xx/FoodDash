<?php

declare(strict_types=1);

require __DIR__ . '/payment_flow_common.php';

$orderId = (int) ($_POST['order_id'] ?? $_GET['order_id'] ?? 0);
$errorMessage = '';
$reference = '---';

if ($orderId <= 0) {
    $errorMessage = 'Invalid order ID.';
} else {
    try {
        $conn = payment_db_conn();
        $order = fetch_order_for_payment($conn, $orderId);

        if ($order === null) {
            $errorMessage = 'Order not found.';
        } else {
            $method = normalize_payment_method((string) ($order['payment_method'] ?? '')) ?? 'gcash';
            $reference = trim((string) ($order['payment_reference'] ?? ''));
            if ($reference === '') {
                $reference = generate_payment_reference($method);
            }

            $status = 'confirmed';
            $paymentStatus = 'paid';
            $stmt = $conn->prepare(
                'UPDATE orders SET payment_status = ?, status = ?, payment_reference = ?, updated_at = NOW() WHERE id = ?'
            );

            if (!$stmt) {
                $errorMessage = 'Failed to prepare payment update.';
            } else {
                $stmt->bind_param('sssi', $paymentStatus, $status, $reference, $orderId);
                if (!$stmt->execute()) {
                    $errorMessage = 'Failed to mark order as paid.';
                }
                $stmt->close();
            }
        }

        $conn->close();
    } catch (Throwable $e) {
        $errorMessage = 'Unexpected error while finalizing payment.';
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Success</title>
    <style>
        :root {
            --good: #0f9d58;
            --good-soft: #e8f9ef;
            --ink: #111827;
            --muted: #6b7280;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 22px;
            font-family: Bahnschrift, "Century Gothic", "Trebuchet MS", sans-serif;
            background: linear-gradient(145deg, #ecfdf3, #d1fae5);
            color: var(--ink);
        }

        .card {
            width: min(470px, 100%);
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 20px 42px rgba(15, 23, 42, 0.12);
        }

        .badge {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 28px;
            background: var(--good-soft);
            color: var(--good);
        }

        h1 { margin: 14px 0 8px; }

        p {
            margin: 0;
            color: var(--muted);
        }

        .meta {
            margin-top: 18px;
            padding: 14px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: #fafafa;
            font-size: 14px;
        }

        .meta strong {
            color: #111827;
        }

        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border: 0;
            border-radius: 12px;
            padding: 11px 14px;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, #047857, #10b981);
            color: #fff;
        }

        .btn-plain {
            background: #f3f4f6;
            color: #111827;
        }
    </style>
</head>
<body>
    <main class="card">
        <?php if ($errorMessage !== ''): ?>
            <div class="badge">!</div>
            <h1>Payment Not Completed</h1>
            <p><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="actions">
                <a href="fake-payment-page.php?order_id=<?= (int) $orderId ?>" class="btn btn-plain">Back to Payment</a>
            </div>
        <?php else: ?>
            <div class="badge">&#10003;</div>
            <h1>Payment Successful</h1>
            <p>Your simulated transaction has been approved. Redirecting to order tracking...</p>
            <div class="meta">
                <div><strong>Order ID:</strong> #<?= (int) $orderId ?></div>
                <div><strong>Reference:</strong> <?= htmlspecialchars($reference, ENT_QUOTES, 'UTF-8') ?></div>
                <div><strong>Payment Status:</strong> paid</div>
                <div><strong>Order Status:</strong> confirmed</div>
            </div>
            <div class="actions">
                <a href="orders" class="btn btn-primary">Go to Tracking</a>
                <a href="/" class="btn btn-plain">Back to Home</a>
            </div>
            <script>
                window.setTimeout(function () {
                    window.location.href = 'orders';
                }, 2200);
            </script>
        <?php endif; ?>
    </main>
</body>
</html>
