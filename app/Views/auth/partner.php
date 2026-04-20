<?php
$activePartnerTab = old('partner_type') === 'restaurant'
    || session()->getFlashdata('restaurant_success')
    || session()->getFlashdata('restaurant_error')
    ? 'restaurant'
    : 'driver';

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
    <title>FoodDash - Rider Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
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

        #restaurantMap {
            height: 320px;
            border: 1px solid var(--fd-border);
            border-radius: 0.75rem;
        }

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
    </style>
</head>

<body class="partner-page">
    <div class="partner-shell">
        <section class="partner-visual" aria-label="Partner hero section">
            <div class="partner-visual-content">
                <p class="partner-visual-kicker">FoodDash Rider Team</p>
                <h1>Ride with FoodDash. Earn on your own schedule.</h1>
                <p>Deliver orders across your area, choose your working hours, and start earning with every completed delivery.</p>
            </div>
        </section>

        <section class="partner-form-pane">
            <div class="partner-topbar">
                <a href="<?php echo site_url('login'); ?>" class="btn btn-outline-secondary btn-sm">
                    ← Back to Login
                </a>
                <h2>Rider Registration</h2>
            </div>

            <div class="partner-form-surface">
                        <!-- Nav Tabs -->
                        <ul class="nav nav-tabs" id="partnerTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= $activePartnerTab === 'driver' ? 'active' : '' ?>" id="driver-tab" data-bs-toggle="tab" data-bs-target="#driver" type="button" role="tab" aria-selected="<?= $activePartnerTab === 'driver' ? 'true' : 'false' ?>">
                                    🏍️ Become a Driver
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= $activePartnerTab === 'restaurant' ? 'active' : '' ?>" id="restaurant-tab" data-bs-toggle="tab" data-bs-target="#restaurant" type="button" role="tab" aria-selected="<?= $activePartnerTab === 'restaurant' ? 'true' : 'false' ?>">
                                    🍽️ Register Restaurant
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content p-4" id="partnerTabContent">
                            
                            <!-- Driver Application Form -->
                            <div class="tab-pane fade <?= $activePartnerTab === 'driver' ? 'show active' : '' ?>" id="driver" role="tabpanel" aria-labelledby="driver-tab">
                                <h4 class="mb-4"><span class="driver-icon">🏍️</span> Driver Application</h4>
                                
                                <!-- Requirements -->
                                <div class="requirements-box">
                                    <h6>📋 Who Can Apply?</h6>
                                    <ul>
                                        <li>Must be at least 18 years old</li>
                                        <li>Valid driver's license for your vehicle type</li>
                                        <li>Own a smartphone with internet connection</li>
                                        <li>Have a clean background record</li>
                                        <li>Own vehicle (motorcycle, car, or a valid bicycle)</li>
                                    </ul>
                                </div>

                                <?php if (session()->getFlashdata('driver_success')): ?>
                                    <div class="alert alert-success">
                                        <?php echo esc(session()->getFlashdata('driver_success')); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (session()->getFlashdata('driver_error')): ?>
                                    <div class="alert alert-danger">
                                        <?php echo esc(session()->getFlashdata('driver_error')); ?>
                                    </div>
                                <?php endif; ?>

                                <form action="<?php echo site_url('partner/register'); ?>" method="post" id="driverForm">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="partner_type" value="driver">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="driver_name" class="form-label">Full Name *</label>
                                                <input type="text" class="form-control" id="driver_name" name="driver_name" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="driver_email" class="form-label">Email Address *</label>
                                                <input type="email" class="form-control" id="driver_email" name="driver_email" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="driver_phone" class="form-label">Phone Number *</label>
                                                <input type="tel" class="form-control" id="driver_phone" name="driver_phone" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="vehicle_type" class="form-label">Vehicle Type *</label>
                                                <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                                                    <option value="">Select vehicle type</option>
                                                    <option value="motorcycle">🏍️ Motorcycle</option>
                                                    <option value="car">🚗 Car</option>
                                                    <option value="bicycle">🚲 Bicycle</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="vehicle_brand" class="form-label">Vehicle Brand/Model</label>
                                                <input type="text" class="form-control" id="vehicle_brand" name="vehicle_brand" placeholder="e.g., Honda PCX, Toyota Vios">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="license_plate" class="form-label">License Plate Number</label>
                                                <input type="text" class="form-control" id="license_plate" name="license_plate" placeholder="e.g., ABC-1234">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="driver_address" class="form-label">Current Address</label>
                                        <textarea class="form-control" id="driver_address" name="driver_address" rows="2" placeholder="Your current residential address"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="driver_notes" class="form-label">Additional Information</label>
                                        <textarea class="form-control" id="driver_notes" name="driver_notes" rows="2" placeholder="Any additional information you'd like to share"></textarea>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="driver_terms" name="driver_terms" required>
                                        <label class="form-check-label" for="driver_terms">
                                            I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#driverTermsModal">Terms and Conditions</a> and privacy policy.
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-primary px-4">Submit Application</button>
                                </form>
                            </div>

                            <!-- Restaurant Registration Form -->
                            <div class="tab-pane fade <?= $activePartnerTab === 'restaurant' ? 'show active' : '' ?>" id="restaurant" role="tabpanel" aria-labelledby="restaurant-tab">
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
                                        <label class="form-label">Pin Restaurant Location on Map</label>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="autoFillAddressFromMap" checked>
                                            <label class="form-check-label" for="autoFillAddressFromMap">Auto-fill address from map</label>
                                        </div>
                                        <div id="restaurantMap"></div>
                                        <small class="text-muted d-block mt-2">Click map to set location. Drag marker to fine-tune.</small>
                                        <div class="row mt-2">
                                            <div class="col-md-6 mb-2 mb-md-0">
                                                <label for="restaurant_latitude" class="form-label">Latitude</label>
                                                <input type="text" class="form-control" id="restaurant_latitude" name="restaurant_latitude" value="<?= old('restaurant_latitude') ?>" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="restaurant_longitude" class="form-label">Longitude</label>
                                                <input type="text" class="form-control" id="restaurant_longitude" name="restaurant_longitude" value="<?= old('restaurant_longitude') ?>" readonly>
                                            </div>
                                        </div>
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
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="owner_phone" class="form-label">Phone Number *</label>
                                                <input type="tel" class="form-control" id="owner_phone" name="owner_phone" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="bank_account" class="form-label">Bank Account Number</label>
                                                <input type="text" class="form-control" id="bank_account" name="bank_account" placeholder="For payout purposes">
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
            </div>
        </section>
    </div>

    <!-- Driver Terms Modal -->
    <div class="modal fade" id="driverTermsModal" tabindex="-1" aria-labelledby="driverTermsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="driverTermsModalLabel">Driver Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Eligibility</h6>
                    <p>You must be at least 18 years old and possess a valid driver's license for your vehicle type.</p>
                    
                    <h6>2. Vehicle Requirements</h6>
                    <p>Your vehicle must be in good condition and properly registered. Regular maintenance is your responsibility.</p>
                    
                    <h6>3. Deliveries</h6>
                    <p>You agree to deliver orders in a timely manner and maintain professional conduct with customers.</p>
                    
                    <h6>4. Earnings</h6>
                    <p>You will receive payments based on completed deliveries. Payment terms are subject to change with notice.</p>
                    
                    <h6>5. Termination</h6>
                    <p>FoodDash reserves the right to terminate your partnership at any time for violation of terms or policies.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function () {
            var mapElement = document.getElementById('restaurantMap');
            if (!mapElement || typeof L === 'undefined') {
                return;
            }

            var latInput = document.getElementById('restaurant_latitude');
            var lngInput = document.getElementById('restaurant_longitude');
            var addressInput = document.getElementById('restaurant_address');
            var autoFillToggle = document.getElementById('autoFillAddressFromMap');

            var map = L.map('restaurantMap').setView([14.5995, 120.9842], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            var marker = null;

            function updateInputs(lat, lng) {
                latInput.value = Number(lat).toFixed(7);
                lngInput.value = Number(lng).toFixed(7);
            }

            function fillAddressFromPoint(lat, lng) {
                if (!addressInput) {
                    return;
                }

                if (autoFillToggle && !autoFillToggle.checked) {
                    return;
                }

                fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng))
                    .then(function (response) {
                        return response.ok ? response.json() : null;
                    })
                    .then(function (result) {
                        if (result && result.display_name) {
                            addressInput.value = result.display_name;
                        }
                    })
                    .catch(function () {
                        // Keep manual address untouched when reverse lookup fails.
                    });
            }

            function setMarker(lat, lng) {
                if (!marker) {
                    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                    marker.on('dragend', function (event) {
                        var point = event.target.getLatLng();
                        updateInputs(point.lat, point.lng);
                        fillAddressFromPoint(point.lat, point.lng);
                    });
                } else {
                    marker.setLatLng([lat, lng]);
                }

                updateInputs(lat, lng);
            }

            map.on('click', function (event) {
                setMarker(event.latlng.lat, event.latlng.lng);
                fillAddressFromPoint(event.latlng.lat, event.latlng.lng);
            });

            if (latInput.value !== '' && lngInput.value !== '') {
                var oldLat = parseFloat(latInput.value);
                var oldLng = parseFloat(lngInput.value);
                if (!isNaN(oldLat) && !isNaN(oldLng)) {
                    map.setView([oldLat, oldLng], 16);
                    setMarker(oldLat, oldLng);
                }
            } else if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    var currentLat = position.coords.latitude;
                    var currentLng = position.coords.longitude;
                    map.setView([currentLat, currentLng], 15);
                    setMarker(currentLat, currentLng);
                });
            }

            var restaurantTab = document.getElementById('restaurant-tab');
            if (restaurantTab) {
                restaurantTab.addEventListener('shown.bs.tab', function () {
                    setTimeout(function () {
                        map.invalidateSize();
                    }, 100);
                });
            }
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
