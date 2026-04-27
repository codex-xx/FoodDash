<?php

declare(strict_types=1);

require __DIR__ . '/payment_flow_common.php';

$orderId = (int) ($_POST['order_id'] ?? $_GET['order_id'] ?? 0);
$errorMessage = '';

if ($orderId <= 0) {
    $errorMessage = 'Invalid order ID.';
} else {
    try {
        $conn = payment_db_conn();
        $order = fetch_order_for_payment($conn, $orderId);

        if ($order === null) {
            $errorMessage = 'Order not found.';
        } else {
            $paymentStatus = 'failed';
            $stmt = $conn->prepare('UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?');

            if (!$stmt) {
                $errorMessage = 'Failed to prepare cancellation update.';
            } else {
                $stmt->bind_param('si', $paymentStatus, $orderId);
                if (!$stmt->execute()) {
                    $errorMessage = 'Failed to mark payment as failed.';
                }
                $stmt->close();
            }
        }

        $conn->close();
    } catch (Throwable $e) {
        $errorMessage = 'Unexpected error while cancelling payment.';
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Cancelled</title>
    <style>
        :root {
            --warn: #b91c1c;
            --warn-soft: #fee2e2;
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
            color: var(--ink);
            background: linear-gradient(145deg, #fff7ed, #ffe4e6);
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
            background: var(--warn-soft);
            color: var(--warn);
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
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
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
        <div class="badge">&#10005;</div>
        <h1>Payment Cancelled</h1>
        <?php if ($errorMessage !== ''): ?>
            <p><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <p>Your payment was not completed. You can retry the transaction anytime.</p>
            <div class="meta">
                <div><strong>Order ID:</strong> #<?= (int) $orderId ?></div>
                <div><strong>Payment Status:</strong> failed</div>
            </div>
        <?php endif; ?>
        <div class="actions">
            <a href="fake-payment-page.php?order_id=<?= (int) $orderId ?>" class="btn btn-primary">Try Again</a>
            <a href="/" class="btn btn-plain">Back to Home</a>
        </div>
    </main>
</body>
</html>
