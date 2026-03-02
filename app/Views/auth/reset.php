<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password — FoodDash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title mb-4 text-center">Reset Password</h4>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger">
                            <?php 
                            $error = session()->getFlashdata('error');
                            if (is_array($error)) {
                                echo '<ul class="mb-0">';
                                foreach ($error as $e) {
                                    echo '<li>' . esc($e) . '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo esc($error);
                            }
                            ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo site_url('reset/' . esc($token)); ?>" method="post">
                        <?php echo csrf_field(); ?>

                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label for="pass_confirm" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="pass_confirm" name="pass_confirm" required>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a class="btn btn-secondary me-2" href="<?php echo site_url('login'); ?>">Back</a>
                            <button class="btn btn-primary">Reset Password</button>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>