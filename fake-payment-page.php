<?php

declare(strict_types=1);

require __DIR__ . '/payment_flow_common.php';

$orderId = (int) ($_GET['order_id'] ?? 0);
$order = null;
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
            $method = normalize_payment_method((string) ($order['payment_method'] ?? '')) ?? 'gcash';
            $reference = trim((string) ($order['payment_reference'] ?? ''));

            if ($reference === '') {
                $reference = generate_payment_reference($method);
                $updateStmt = $conn->prepare('UPDATE orders SET payment_reference = ?, updated_at = NOW() WHERE id = ?');

                if ($updateStmt) {
                    $updateStmt->bind_param('si', $reference, $orderId);
                    $updateStmt->execute();
                    $updateStmt->close();
                }

                $order['payment_reference'] = $reference;
            }

            $order['payment_method'] = $method;
        }

        $conn->close();
    } catch (Throwable $e) {
        $errorMessage = 'Unable to load payment screen right now.';
    }
}

$method = $order['payment_method'] ?? 'gcash';
$isMaya = $method === 'paymaya';
$brandName = payment_brand_label($method);
$amount = number_format((float) ($order['total_amount'] ?? 0), 2);
$reference = (string) ($order['payment_reference'] ?? '---');

$primaryColor = $isMaya ? '#00a86b' : '#0057ff';
$secondaryColor = $isMaya ? '#e8fff4' : '#e8f0ff';
$accentColor = $isMaya ? '#006b45' : '#0037aa';

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pay with <?= htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            --brand: <?= htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8') ?>;
            --brand-soft: <?= htmlspecialchars($secondaryColor, ENT_QUOTES, 'UTF-8') ?>;
            --brand-dark: <?= htmlspecialchars($accentColor, ENT_QUOTES, 'UTF-8') ?>;
            --ink: #111827;
            --muted: #6b7280;
            --card: #ffffff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Bahnschrift, "Century Gothic", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.35), transparent 40%),
                linear-gradient(145deg, var(--brand-dark), var(--brand));
            display: grid;
            place-items: center;
            padding: 22px;
        }

        .shell {
            width: min(430px, 100%);
            background: var(--card);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.26);
            animation: rise 0.45s ease;
        }

        @keyframes rise {
            from { transform: translateY(10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .top {
            background: var(--brand-soft);
            padding: 22px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        }

        .brand-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--brand-dark), var(--brand));
            color: #fff;
            font-weight: 700;
            display: grid;
            place-items: center;
            letter-spacing: 0.5px;
        }

        .brand-label {
            margin: 0;
            font-size: 21px;
            font-weight: 700;
        }

        .tagline {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .content {
            padding: 22px;
        }

        .amount {
            font-size: 40px;
            font-weight: 800;
            line-height: 1;
            margin: 8px 0 10px;
        }

        .caption {
            margin: 0;
            color: var(--muted);
            letter-spacing: 0.3px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .info-box {
            margin-top: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 14px;
            background: #fafafa;
        }

        .kv {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 7px;
            font-size: 14px;
        }

        .kv span:first-child {
            color: var(--muted);
        }

        .actions {
            display: grid;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            border: 0;
            border-radius: 14px;
            height: 48px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.15s ease, opacity 0.2s ease;
            font-family: inherit;
        }

        .btn:active {
            transform: translateY(1px);
        }

        .btn-pay {
            background: linear-gradient(135deg, var(--brand-dark), var(--brand));
            color: #fff;
        }

        .btn-cancel {
            background: #f3f4f6;
            color: #111827;
        }

        .loading {
            position: fixed;
            inset: 0;
            display: none;
            place-items: center;
            background: rgba(3, 7, 18, 0.5);
            color: #fff;
            font-size: 15px;
            backdrop-filter: blur(2px);
        }

        .loading.visible {
            display: grid;
        }

        .error {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            width: min(420px, 100%);
        }

        @media (max-width: 480px) {
            .amount {
                font-size: 34px;
            }
        }
    </style>
</head>
<body>
<?php if ($errorMessage !== ''): ?>
    <div class="error">
        <h2>Payment page unavailable</h2>
        <p><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
<?php else: ?>
    <main class="shell">
        <section class="top">
            <div class="brand-row">
                <div class="brand-logo"><?= $isMaya ? 'MY' : 'GC' ?></div>
                <div>
                    <h1 class="brand-label">Pay with <?= htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="tagline"><?= $isMaya ? 'Secure Payment' : 'Fast and secure checkout' ?></p>
                </div>
            </div>
        </section>

        <section class="content">
            <p class="caption">Total Amount</p>
            <p class="amount">PHP <?= htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') ?></p>

            <div class="info-box">
                <div class="kv">
                    <span>Order ID</span>
                    <strong>#<?= (int) $orderId ?></strong>
                </div>
                <div class="kv">
                    <span>Reference</span>
                    <strong><?= htmlspecialchars($reference, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>

            <div class="actions">
                <form method="post" action="payment-success.php?order_id=<?= (int) $orderId ?>" id="payForm">
                    <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
                    <button type="submit" class="btn btn-pay">Pay Now</button>
                </form>

                <form method="post" action="payment-failed.php?order_id=<?= (int) $orderId ?>">
                    <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
                    <button type="submit" class="btn btn-cancel">Cancel</button>
                </form>
            </div>
        </section>
    </main>

    <div class="loading" id="loadingState">Authorizing your payment...</div>

    <script>
        const payForm = document.getElementById('payForm');
        const loading = document.getElementById('loadingState');

        payForm.addEventListener('submit', function () {
            loading.classList.add('visible');
        });
    </script>
<?php endif; ?>
</body>
</html>
