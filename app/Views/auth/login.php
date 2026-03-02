<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FoodDash - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --fd-mustard: #F2C200;
            --fd-sand: #F3D39A;
            --fd-espresso: #241C0C;
            --fd-slate: #6B7C87;
            --fd-stone: #CFC6BA;
            --fd-charcoal: #3A3F45;

            --fd-primary: var(--fd-mustard);
            --fd-border: rgba(58, 63, 69, 0.18);
            --fd-white: #FFFFFF;
            --fd-bg: #F6F3EE;
        }

        body.login-page {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 20% 10%, rgba(242, 194, 0, 0.18), rgba(255, 255, 255, 0) 45%),
                        linear-gradient(180deg, #FFFFFF 0%, var(--fd-bg) 60%, rgba(207, 198, 186, 0.55) 100%);
            color: var(--fd-espresso);
        }

        .login-card {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(243, 211, 154, 0.14));
            border: 1px solid var(--fd-border);
            color: var(--fd-espresso);
            border-radius: 1rem;
        }

        .login-card .form-label {
            color: rgba(36, 28, 12, 0.78);
        }

        .login-card .form-control {
            background-color: rgba(255, 255, 255, 0.95);
            border-color: rgba(58, 63, 69, 0.25);
            color: var(--fd-espresso);
        }

        .login-card .form-control:focus {
            border-color: rgba(242, 194, 0, 0.7);
            box-shadow: 0 0 0 0.15rem rgba(242, 194, 0, 0.28);
        }

        .login-card .btn-primary {
            background-color: var(--fd-primary);
            border-color: var(--fd-primary);
            color: var(--fd-espresso);
        }

        .login-card .btn-primary:hover,
        .login-card .btn-primary:focus {
            background-color: #FFD54A;
            border-color: #FFD54A;
            color: var(--fd-espresso);
        }

        .login-logo {
            font-weight: 700;
            letter-spacing: 0.05em;
            color: var(--fd-espresso);
        }

        .login-card a {
            color: var(--fd-slate);
        }

        .login-card a:hover {
            color: var(--fd-espresso);
        }
    </style>
</head>

<body class="login-page">
<div class="container">
    <!-- Help Centre and Be Our Partner Links -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-5 d-flex justify-content-between">
            <a href="<?php echo site_url('help'); ?>" class="btn btn-outline-secondary btn-sm">
                ❓ Help Centre
            </a>
            <a href="<?php echo site_url('partner'); ?>" class="btn btn-outline-primary btn-sm">
                🤝 Be Our Partner
            </a>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm login-card">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="login-logo">🏍️ FoodDash</div>
                        <small class="text-muted">Sign in to your dashboard</small>
                    </div>

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

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <a href="<?php echo site_url('forgot'); ?>" class="text-decoration-none">Forgot Password?</a>
                            <button class="btn btn-primary px-4">Login</button>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>