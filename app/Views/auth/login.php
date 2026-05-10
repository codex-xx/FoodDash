<?php
$loginWallpaperRel = 'uploads/logos/WALLAPAPER_2.png';
$loginWallpaperAbs = FCPATH . $loginWallpaperRel;
$hasLoginWallpaper = is_file($loginWallpaperAbs);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FoodDash - Login</title>
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
            --fd-shadow: 0 28px 70px rgba(14, 24, 33, 0.28);
        }

        body.login-page {
            min-height: 100vh;
            margin: 0;
            font-family: 'Manrope', sans-serif;
            color: var(--fd-ink);
            position: relative;
            overflow-x: hidden;
            background: linear-gradient(180deg, #DFE8EE 0%, #E9EFF2 100%);
        }

        .login-page::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -2;
<?php if ($hasLoginWallpaper): ?>
            background-image: linear-gradient(125deg, rgba(10, 20, 30, 0.36), rgba(8, 38, 55, 0.3), rgba(10, 26, 38, 0.34)), url('<?= base_url($loginWallpaperRel) ?>');
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
<?php else: ?>
            background: radial-gradient(circle at 12% 8%, rgba(15, 122, 138, 0.28), rgba(0, 0, 0, 0) 42%),
                        radial-gradient(circle at 86% 92%, rgba(227, 163, 35, 0.24), rgba(0, 0, 0, 0) 36%),
                        linear-gradient(150deg, #122838 0%, #1D3347 52%, #172A3A 100%);
<?php endif; ?>
        }

        .login-page::after {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            background: radial-gradient(circle at 20% 18%, rgba(255, 255, 255, 0.05), rgba(0, 0, 0, 0) 44%),
                        radial-gradient(circle at 84% 90%, rgba(15, 122, 138, 0.08), rgba(0, 0, 0, 0) 35%);
        }

        .login-stage {
            min-height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .login-shell {
            width: min(430px, 92vw);
            border-radius: 1.4rem;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: 0 16px 40px rgba(14, 24, 33, 0.18);
            backdrop-filter: blur(2px);
            background: rgba(245, 248, 250, 0.04);
        }

        .form-panel {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.46), rgba(247, 250, 252, 0.4));
            padding: 1rem;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .panel-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.7rem;
            margin-bottom: 1rem;
        }

        .panel-links {
            display: flex;
            gap: 0.55rem;
            flex-wrap: wrap;
        }

        .btn-utility {
            border: 1px solid rgba(22, 32, 42, 0.24);
            color: var(--fd-ink);
            background: rgba(255, 255, 255, 0.92);
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 0.65rem;
            padding: 0.38rem 0.65rem;
            text-decoration: none;
        }

        .btn-utility:hover {
            background: #FFFFFF;
            color: var(--fd-ink);
            border-color: rgba(22, 32, 42, 0.34);
        }

        .login-card {
            background: rgba(255, 255, 255, 0.52);
            border: 1px solid rgba(22, 32, 42, 0.14);
            border-radius: 1rem;
            box-shadow: 0 8px 18px rgba(19, 35, 48, 0.1);
            backdrop-filter: blur(2px);
            padding: 1rem;
        }

        .login-card .form-label {
            color: var(--fd-ink-soft);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
        }

        .login-title {
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.2rem;
            color: var(--fd-ink);
        }

        .login-subtitle {
            margin: 0.35rem 0 0;
            color: rgba(58, 74, 89, 0.84);
            font-size: 0.85rem;
        }

        .login-card .form-control {
            background-color: rgba(255, 255, 255, 0.82);
            border-color: rgba(58, 74, 89, 0.3);
            color: var(--fd-ink);
            border-radius: 0.75rem;
            padding: 0.68rem 0.8rem;
        }

        .login-card .form-control:focus {
            border-color: rgba(15, 122, 138, 0.8);
            box-shadow: 0 0 0 0.2rem rgba(15, 122, 138, 0.18);
        }

        .btn-login {
            border: none;
            background: linear-gradient(120deg, var(--fd-ocean) 0%, var(--fd-ocean-deep) 100%);
            color: #FFFFFF;
            border-radius: 0.75rem;
            padding: 0.62rem 1.2rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .btn-login:hover,
        .btn-login:focus {
            background: linear-gradient(120deg, #108B9D 0%, #0A6674 100%);
            color: #FFFFFF;
        }

        .login-footlink {
            font-size: 0.9rem;
            color: var(--fd-ink-soft);
            text-decoration: none;
            font-weight: 600;
        }

        .login-footlink:hover {
            color: var(--fd-ocean-deep);
        }

        @media (max-width: 991px) {
            .form-panel {
                padding: 1rem;
            }
        }

        @media (max-width: 575px) {
            .login-stage {
                padding: 0.65rem;
            }

            .login-card {
                padding: 1rem;
            }
        }
    </style>
</head>

<body class="login-page">
    <main class="login-stage">
        <section class="login-shell" aria-label="FoodDash login">
            <section class="form-panel">
                <div class="panel-topbar">
                    <div class="panel-links">
                        <a href="<?php echo site_url('help'); ?>" class="btn-utility">Help Centre</a>
                        <a href="<?php echo site_url('partner'); ?>" class="btn-utility">Be Our Partner</a>
                    </div>
                </div>

                <div class="login-card">
                    <header class="mb-3">
                        <h2 class="login-title">Sign in</h2>
                        <p class="login-subtitle">FoodDash</p>
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
                        <div class="alert alert-success"><?php echo esc(session()->getFlashdata('success')); ?></div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('security_warning')): ?>
                        <div class="alert alert-warning"><?php echo esc(session()->getFlashdata('security_warning')); ?></div>
                    <?php endif; ?>

                    <form action="<?php echo site_url('login'); ?>" method="post">
                        <?php echo csrf_field(); ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo set_value('email'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <a href="<?php echo site_url('forgot'); ?>" class="login-footlink">Forgot password?</a>
                            <button class="btn btn-login" type="submit">Login</button>
                        </div>
                    </form>
                </div>
            </section>
        </section>
    </main>
</body>
</html>