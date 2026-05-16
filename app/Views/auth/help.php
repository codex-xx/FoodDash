<?php
$pageWallpaperRel = 'uploads/logos/Wallpaper.png';
$pageWallpaperAbs = FCPATH . $pageWallpaperRel;
$hasPageWallpaper = is_file($pageWallpaperAbs);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FoodDash - Help Centre</title>
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

        body.help-page {
            min-height: 100vh;
            margin: 0;
<?php if ($hasPageWallpaper): ?>
            background-image: linear-gradient(135deg, rgba(36, 28, 12, 0.65), rgba(36, 28, 12, 0.42)), url('<?= base_url($pageWallpaperRel) ?>');
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
<?php else: ?>
            background: linear-gradient(180deg, #FFFFFF 0%, var(--fd-bg) 60%, rgba(207, 198, 186, 0.55) 100%);
<?php endif; ?>
            color: var(--fd-espresso);
            padding: 2rem 0;
        }

        .help-card {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid var(--fd-border);
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .help-header {
            background: linear-gradient(135deg, var(--fd-mustard) 0%, #FFD54A 100%);
            border-radius: 1rem 1rem 0 0;
            padding: 2rem;
            text-align: center;
        }

        .help-header h1 {
            color: var(--fd-espresso);
            font-weight: 700;
            margin: 0;
        }

        .contact-section {
            border-left: 4px solid var(--fd-mustard);
            padding-left: 1rem;
            margin-bottom: 1.5rem;
        }

        .contact-section.customers { border-color: #28a745; }
        .contact-section.drivers { border-color: #007bff; }
        .contact-section.restaurants { border-color: #fd7e14; }

        .contact-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }

        .back-btn {
            position: absolute;
            top: 1rem;
            left: 1rem;
        }
    </style>
</head>

<body class="help-page">
    <div class="container position-relative">
        <a href="<?php echo site_url('login'); ?>" class="btn btn-outline-secondary back-btn">
            ← Back to Login
        </a>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="help-card">
                    <div class="help-header">
                        <h1>❓ Help Centre</h1>
                        <p class="mb-0">We're here to help! Contact us for any issues or feedback.</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <h4 class="mb-4">📞 Contact Us</h4>

                        <!-- Customer Support -->
                        <div class="contact-section customers">
                            <h5><span class="contact-icon">👤</span>For Customers</h5>
                            <p>Having issues with ordering, payments, or delivery? We're here to help!</p>
                            <div class="mb-3">
                                <strong>Email:</strong> 
                                <a href="mailto:support@fooddash.com">support@fooddash.com</a>
                            </div>
                            <p class="text-muted small">
                                <em>Response time: Within 24 hours</em>
                            </p>
                        </div>

                        <!-- Driver Support -->
                        <div class="contact-section drivers">
                            <h5><span class="contact-icon">🏍️</span>For Drivers</h5>
                            <p>Questions about deliveries, earnings, or your driver account? Contact us!</p>
                            <div class="mb-3">
                                <strong>Email:</strong> 
                                <a href="mailto:drivers@fooddash.com">drivers@fooddash.com</a>
                            </div>
                            <p class="text-muted small">
                                <em>Response time: Within 12 hours</em>
                            </p>
                        </div>

                        <!-- Restaurant Support -->
                        <div class="contact-section restaurants">
                            <h5><span class="contact-icon">🍽️</span>For Restaurant Owners</h5>
                            <p>Questions about your restaurant account, menu, or orders? Get in touch!</p>
                            <div class="mb-3">
                                <strong>Email:</strong> 
                                <a href="mailto:merchants@fooddash.com">merchants@fooddash.com</a>
                            </div>
                            <p class="text-muted small">
                                <em>Response time: Within 24 hours</em>
                            </p>
                        </div>

                        <hr class="my-4">

                        <!-- General Inquiries -->
                        <div class="contact-section">
                            <h5><span class="contact-icon">📧</span>General Inquiries</h5>
                            <p>For all other questions or feedback about FoodDash:</p>
                            <div class="mb-3">
                                <strong>Email:</strong> 
                                <a href="mailto:info@fooddash.com">info@fooddash.com</a>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Common Issues -->
                        <h4 class="mb-3">🔧 Common Issues</h4>
                        
                        <div class="accordion" id="helpAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                        How do I reset my password?
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        Click on "Forgot Password" on the login page and enter your email address. You'll receive a reset link to create a new password.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                        How do I track my order?
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        Once your order is confirmed, you can track its status in real-time through the app. You'll receive updates when the restaurant starts preparing your food and when the driver picks it up.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                                        How do I register my restaurant?
                                    </button>
                                </h2>
                                <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        Click on "Be Our Partner" on the login page and fill out the restaurant owner registration form. You'll need to provide your business license and restaurant details. Our team will contact you after verification.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <p class="text-muted">Thank you for choosing FoodDash! 💛</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
