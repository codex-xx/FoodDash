<?php
$email = (string) session()->get('mfa_pending_email');
$maskedEmail = $email;
if ($email !== '' && strpos($email, '@') !== false) {
    [$local, $domain] = explode('@', $email, 2);
    $maskedLocal = strlen($local) > 2 ? substr($local, 0, 2) . str_repeat('*', max(1, strlen($local) - 2)) : str_repeat('*', max(1, strlen($local)));
    $maskedEmail = $maskedLocal . '@' . $domain;
}

$mfaExpiry = (string) session()->get('mfa_otp_expires_at');
$mfaExpiryText = $mfaExpiry !== '' ? date('g:i A', strtotime($mfaExpiry)) : '5 minutes';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Login - FoodDash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --fd-ink: #16202A;
            --fd-ink-soft: #3A4A59;
            --fd-ocean: #0F7A8A;
            --fd-ocean-deep: #075B67;
            --fd-amber: #E3A323;
            --fd-paper: #F5F8FA;
            --fd-white: #FFFFFF;
            --fd-border: rgba(22, 32, 42, 0.16);
        }

        body.verify-page {
            min-height: 100vh;
            margin: 0;
            font-family: 'Manrope', sans-serif;
            color: var(--fd-ink);
            background: linear-gradient(180deg, #DFE8EE 0%, #E9EFF2 100%);
            position: relative;
            overflow-x: hidden;
        }

        .verify-page::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -2;
            background: radial-gradient(circle at 12% 8%, rgba(15, 122, 138, 0.28), rgba(0, 0, 0, 0) 42%),
                        radial-gradient(circle at 86% 92%, rgba(227, 163, 35, 0.24), rgba(0, 0, 0, 0) 36%),
                        linear-gradient(150deg, #122838 0%, #1D3347 52%, #172A3A 100%);
        }

        .verify-page::after {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            background: radial-gradient(circle at 20% 18%, rgba(255, 255, 255, 0.05), rgba(0, 0, 0, 0) 44%),
                        radial-gradient(circle at 84% 90%, rgba(15, 122, 138, 0.08), rgba(0, 0, 0, 0) 35%);
        }

        .verify-stage {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .verify-shell {
            width: min(430px, 92vw);
            border-radius: 1.4rem;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: 0 16px 40px rgba(14, 24, 33, 0.18);
            background: rgba(245, 248, 250, 0.04);
            backdrop-filter: blur(2px);
        }

        .verify-card {
            background: rgba(255, 255, 255, 0.52);
            border: 1px solid rgba(22, 32, 42, 0.14);
            border-radius: 1rem;
            box-shadow: 0 8px 18px rgba(19, 35, 48, 0.1);
            backdrop-filter: blur(2px);
            padding: 1rem;
        }

        .verify-title {
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.2rem;
        }

        .verify-subtitle {
            margin: 0.35rem 0 0;
            color: rgba(58, 74, 89, 0.84);
            font-size: 0.85rem;
        }

        .verify-card .form-label {
            color: var(--fd-ink-soft);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
        }

        .verify-card .form-control {
            background-color: rgba(255, 255, 255, 0.82);
            border-color: rgba(58, 74, 89, 0.3);
            color: var(--fd-ink);
            border-radius: 0.75rem;
            padding: 0.68rem 0.8rem;
            letter-spacing: 0.4em;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .verify-card .form-control:focus {
            border-color: rgba(15, 122, 138, 0.8);
            box-shadow: 0 0 0 0.2rem rgba(15, 122, 138, 0.18);
        }

        .btn-verify {
            border: none;
            background: linear-gradient(120deg, var(--fd-ocean) 0%, var(--fd-ocean-deep) 100%);
            color: #FFFFFF;
            border-radius: 0.75rem;
            padding: 0.62rem 1.2rem;
            font-weight: 700;
        }

        .btn-verify:hover,
        .btn-verify:focus {
            background: linear-gradient(120deg, #108B9D 0%, #0A6674 100%);
            color: #FFFFFF;
        }

        .verify-footlink {
            font-size: 0.9rem;
            color: var(--fd-ink-soft);
            text-decoration: none;
            font-weight: 600;
        }

        .verify-footlink:hover {
            color: var(--fd-ocean-deep);
        }
    </style>
</head>
<body class="verify-page">
<main class="verify-stage">
    <section class="verify-shell" aria-label="Login verification">
        <div class="verify-card">
            <header class="mb-3">
                <h2 class="verify-title">Verify your login</h2>
                <p class="verify-subtitle">We sent a 6-digit code to <?= esc($maskedEmail !== '' ? $maskedEmail : 'your email address') ?>.</p>
            </header>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger">
                    <?php $err = session()->getFlashdata('error');
                    if (is_array($err)) {
                        foreach ($err as $e) { echo esc($e) . '<br>'; }
                    } else { echo esc($err); }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>

            <p class="small text-muted mb-3">The code expires at <?= esc($mfaExpiryText) ?>. If it expires, sign in again to receive a new code.</p>

            <form action="<?= site_url('mfa/verify') ?>" method="post">
                <?= csrf_field(); ?>
                <div class="mb-3">
                    <label for="otp" class="form-label">Verification code</label>
                    <input type="text" class="form-control" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-verify" type="submit">Verify code</button>
                </div>
            </form>

            <div class="d-flex justify-content-start mt-3">
                <a href="<?= site_url('login') ?>" class="verify-footlink" onclick="window.location.href=this.href; return false;">Back to login</a>
            </div>
        </div>
    </section>
</main>
</body>
</html>