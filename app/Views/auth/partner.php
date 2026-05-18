<?php
$activePartnerTab = 'restaurant';

$pageWallpaperRel = null;
foreach (['upload/logo/Merchant.png', 'logos/Merchant.png', 'uploads/logos/Merchant.png'] as $candidateRel) {
    if (is_file(FCPATH . $candidateRel)) {
        $pageWallpaperRel = $candidateRel;
        break;
    }
}
$hasPageWallpaper = $pageWallpaperRel !== null;
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FoodDash - Restaurant Registration</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Leaflet map removed -->
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

        body.partner-page {
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(180deg, #F5F1E7 0%, #EEE6D7 100%);
            color: var(--fd-espresso);
            padding: 1rem;
        }

        .partner-shell {
            max-width: 1440px;
            margin: 0 auto;
            min-height: calc(100vh - 2rem);
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            border-radius: 1.25rem;
            overflow: hidden;
            border: 1px solid rgba(58, 63, 69, 0.14);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.14);
        }

        .partner-visual {
            position: relative;
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            padding: 4rem;
            color: #FFFFFF;
            background: linear-gradient(140deg, #1A6A52 0%, #2B8C6A 48%, #6AB06A 100%);
            overflow: hidden;
        }

        .partner-visual::before {
            content: "";
            position: absolute;
            inset: 0;
<?php if ($hasPageWallpaper): ?>
            background-image: linear-gradient(120deg, rgba(0, 0, 0, 0.42) 0%, rgba(0, 0, 0, 0.52) 36%, rgba(0, 0, 0, 0.2) 100%), url('<?= base_url($pageWallpaperRel) ?>');
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
<?php endif; ?>
        }

        .partner-visual-content {
            position: relative;
            z-index: 1;
            max-width: 640px;
        }

        .partner-visual-kicker {
            margin: 0 0 0.75rem;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.88);
        }

        .partner-visual h1 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 4rem);
            line-height: 1.05;
            font-weight: 700;
            text-wrap: balance;
        }

        .partner-visual p {
            margin: 1.1rem 0 0;
            max-width: 34ch;
            font-size: clamp(1rem, 1.4vw, 1.5rem);
            line-height: 1.35;
            color: rgba(255, 255, 255, 0.9);
        }

        .partner-form-pane {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(4px);
            padding: 1.25rem 1.25rem 0;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        /* Map styles removed */

        .partner-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.9rem;
        }

        .partner-topbar h2 {
            margin: 0;
            font-size: 1.02rem;
            color: rgba(36, 28, 12, 0.72);
            letter-spacing: 0.01em;
        }

        .partner-form-surface {
            border: 1px solid var(--fd-border);
            border-radius: 1rem 1rem 0 0;
            background: rgba(255, 255, 255, 0.98);
            overflow-y: auto;
            min-height: 0;
        }

        .nav-tabs .nav-link {
            color: var(--fd-slate);
            border: none;
            padding: 1rem 2rem;
            font-weight: 600;
        }

        .nav-tabs .nav-link.active {
            background: var(--fd-white);
            color: var(--fd-espresso);
            border-bottom: 3px solid #1FA565;
        }

        .nav-tabs .nav-link:hover {
            border-color: transparent;
        }

        .form-label {
            color: rgba(36, 28, 12, 0.78);
            font-weight: 500;
        }

        .form-control {
            background-color: rgba(255, 255, 255, 0.95);
            border-color: rgba(58, 63, 69, 0.25);
            color: var(--fd-espresso);
        }

        .form-control:focus {
            border-color: rgba(242, 194, 0, 0.7);
            box-shadow: 0 0 0 0.15rem rgba(242, 194, 0, 0.28);
        }

        .btn-primary {
            background-color: var(--fd-primary);
            border-color: var(--fd-primary);
            color: var(--fd-espresso);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: #FFD54A;
            border-color: #FFD54A;
            color: var(--fd-espresso);
        }

        .requirements-box {
            background: rgba(242, 194, 0, 0.1);
            border: 1px solid rgba(242, 194, 0, 0.3);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .requirements-box h6 {
            color: var(--fd-espresso);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .requirements-box ul {
            margin-bottom: 0;
            padding-left: 1.25rem;
        }

        .requirements-box li {
            font-size: 0.9rem;
            color: var(--fd-charcoal);
        }

        @media (max-width: 1199px) {
            .partner-shell {
                grid-template-columns: 1fr;
            }

            .partner-visual {
                min-height: 280px;
                padding: 2rem 1.5rem;
            }

            .partner-visual p {
                max-width: 44ch;
            }

            .partner-form-pane {
                padding: 1rem 1rem 0;
            }
        }

        @media (max-width: 767px) {
            body.partner-page {
                padding: 0;
            }

            .partner-shell {
                border-radius: 0;
                min-height: 100vh;
                border-left: none;
                border-right: none;
            }

            .partner-visual {
                min-height: 240px;
                padding: 1.5rem 1rem;
            }

            .partner-topbar {
                flex-wrap: wrap;
            }

            .nav-tabs .nav-link {
                padding: 0.8rem 1rem;
                font-size: 0.95rem;
            }
        }

        .driver-icon { color: #007bff; }
        .restaurant-icon { color: #fd7e14; }
        #restaurant-location-map {
            height: 320px;
            border-radius: 0.9rem;
            border: 1px solid rgba(58, 63, 69, 0.16);
            overflow: hidden;
            background: linear-gradient(180deg, #eef7f2 0%, #e6efe9 100%);
        }

        .location-note {
            color: var(--fd-slate);
            font-size: 0.92rem;
        }
    </style>
</head>

<body class="partner-page">
    <div class="partner-shell">
        <section class="partner-visual" aria-label="Partner hero section">
            <div class="partner-visual-content">
                <p class="partner-visual-kicker">FoodDash Restaurant Partners</p>
                <h1>Join FoodDash as a restaurant partner.</h1>
                <p>List your restaurant, manage orders more easily, and reach more customers through the FoodDash platform.</p>
            </div>
        </section>

        <section class="partner-form-pane">
            <div class="partner-topbar">
                <a href="<?php echo site_url('login'); ?>" class="btn btn-outline-secondary btn-sm">
                    ← Back to Login
                </a>
                <h2>Restaurant Registration</h2>
            </div>

            <div class="partner-form-surface">
                        <div class="p-4">
                            <h4 class="mb-4"><span class="restaurant-icon">🍽️</span> Restaurant Owner Registration</h4>

                            <!-- Requirements -->
                            <div class="requirements-box">
                                <h6>📋 Requirements to Register</h6>
                                <ul>
                                    <li>Valid business license or registration</li>
                                    <li>Physical restaurant location</li>
                                    <li>Ability to prepare food for delivery</li>
                                    <li>Owner's identification</li>
                                    <li>Bank account for payouts</li>
                                </ul>
                            </div>

                            <?php if (session()->getFlashdata('restaurant_success')): ?>
                                <div class="alert alert-success">
                                    <?php echo esc(session()->getFlashdata('restaurant_success')); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (session()->getFlashdata('restaurant_error')): ?>
                                <div class="alert alert-danger">
                                    <?php echo esc(session()->getFlashdata('restaurant_error')); ?>
                                </div>
                            <?php endif; ?>

                            <form action="<?php echo site_url('partner/register'); ?>" method="post" id="restaurantForm">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="partner_type" value="restaurant">

                                <h6 class="mt-3 mb-2">🏪 Restaurant Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="restaurant_name" class="form-label">Restaurant Name *</label>
                                            <input type="text" class="form-control" id="restaurant_name" name="restaurant_name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="restaurant_phone" class="form-label">Restaurant Phone *</label>
                                            <input type="tel" class="form-control" id="restaurant_phone" name="restaurant_phone" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="restaurant_address" class="form-label">Restaurant Address *</label>
                                    <textarea class="form-control" id="restaurant_address" name="restaurant_address" rows="2" required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="delivery_radius_km" class="form-label">Delivery Radius (km) *</label>
                                    <input type="number" class="form-control" id="delivery_radius_km" name="delivery_radius_km" min="0.1" step="0.1" placeholder="e.g., 5" required>
                                    <div class="form-text">This will define how far your restaurant can deliver for now. You can edit it later in settings.</div>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                                        <div>
                                            <label class="form-label mb-1">Restaurant Location</label>
                                            <div class="location-note">Drag the pin or use your current location to fill the address and coordinates automatically.</div>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="useCurrentLocationBtn">
                                            Use My Current Location
                                        </button>
                                    </div>

                                    <div id="restaurant-location-map" data-initial-address="" data-initial-lat="" data-initial-lng=""></div>
                                    <input type="hidden" id="restaurant_latitude" name="restaurant_latitude">
                                    <input type="hidden" id="restaurant_longitude" name="restaurant_longitude">
                                    <div id="locationStatus" class="location-note mt-2">Choose a location on the map. The address will update automatically.</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cuisine_type" class="form-label">Cuisine Type</label>
                                            <input type="text" class="form-control" id="cuisine_type" name="cuisine_type" placeholder="e.g., Italian, Chinese, Filipino">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="business_license" class="form-label">Business License Number</label>
                                            <input type="text" class="form-control" id="business_license" name="business_license">
                                        </div>
                                    </div>
                                </div>

                                <h6 class="mt-4 mb-2">👤 Owner/Contact Person Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="owner_name" class="form-label">Full Name *</label>
                                            <input type="text" class="form-control" id="owner_name" name="owner_name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="owner_email" class="form-label">Email Address *</label>
                                            <input type="email" class="form-control" id="owner_email" name="owner_email" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="owner_phone" class="form-label">Phone Number *</label>
                                            <input type="tel" class="form-control" id="owner_phone" name="owner_phone" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="owner_password" class="form-label">Password *</label>
                                            <input type="password" class="form-control" id="owner_password" name="owner_password" minlength="8" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="restaurant_notes" class="form-label">Additional Information</label>
                                    <textarea class="form-control" id="restaurant_notes" name="restaurant_notes" rows="2" placeholder="Any additional information about your restaurant"></textarea>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="restaurant_terms" name="restaurant_terms" required>
                                    <label class="form-check-label" for="restaurant_terms">
                                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#restaurantTermsModal">Terms and Conditions</a> and privacy policy.
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary px-4">Submit Registration</button>
                            </form>
                        </div>
                    </div>
            </div>
        </section>
    </div>

    <!-- Restaurant Terms Modal -->
    <div class="modal fade" id="restaurantTermsModal" tabindex="-1" aria-labelledby="restaurantTermsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restaurantTermsModalLabel">Restaurant Partner Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Business Requirements</h6>
                    <p>You must have a valid business license and comply with all local health and safety regulations.</p>
                    
                    <h6>2. Food Quality</h6>
                    <p>All food must be prepared fresh and meet quality standards. FoodDash reserves the right to reject items that do not meet requirements.</p>
                    
                    <h6>3. Order Fulfillment</h6>
                    <p>You agree to prepare and have orders ready for pickup within the specified time frame.</p>
                    
                    <h6>4. Commission</h6>
                    <p>FoodDash charges a commission on each order. The current rate is available in your partner dashboard.</p>
                    
                    <h6>5. Payouts</h6>
                    <p>Payouts are processed weekly to your registered bank account.</p>
                    
                    <h6>6. Termination</h6>
                    <p>FoodDash reserves the right to terminate your partnership at any time for violation of terms or policies.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.FoodDashRestaurantLocationMapConfig = {
            mapElementId: 'restaurant-location-map',
            latitudeInputId: 'restaurant_latitude',
            longitudeInputId: 'restaurant_longitude',
            addressInputId: 'restaurant_address',
            useCurrentLocationButtonId: 'useCurrentLocationBtn',
            statusElementId: 'locationStatus',
            editableByDefault: true
        };
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="<?= base_url('js/restaurant-location-map.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
