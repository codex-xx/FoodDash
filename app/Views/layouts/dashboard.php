<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($pageTitle ?? 'Dashboard - FoodDash') ?></title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
        <!-- Early theme application script: reads localStorage or cookie and sets html[data-theme] before CSS loads -->
        <script>
            (function(){
                try {
                    var key = 'fooddash-theme-preference';
                    var theme = null;
                    try { theme = localStorage.getItem(key); } catch(e){}
                    if (!theme) {
                        try {
                            var parts = document.cookie.split(';').map(function(p){return p.trim();});
                            for (var i=0;i<parts.length;i++){
                                if (parts[i].indexOf(key+'=')===0){ theme = parts[i].substring((key+'=').length); break; }
                            }
                        } catch(e){}
                    }
                    if (theme === 'dark' || theme === 'light') {
                        document.documentElement.setAttribute('data-theme', theme);
                        document.documentElement.dataset.theme = theme;
                    }
                } catch(e){}
            })();
        </script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Load enhanced theme CSS first for highest priority -->
        <link href="<?= base_url('css/themes-enhanced.css') ?>" rel="stylesheet">
        <link href="<?= base_url('css/themes-comprehensive.css') ?>" rel="stylesheet">
        <link href="<?= base_url('css/themes.css') ?>" rel="stylesheet">
    <style>
        :root {
            /* Palette from provided image */
            --fd-mustard: #F2C200;
            --fd-mustard-soft: rgba(242, 194, 0, 0.18);
            --fd-sand: #F3D39A;
            --fd-espresso: #241C0C;
            --fd-slate: #6B7C87;
            --fd-stone: #CFC6BA;
            --fd-charcoal: #3A3F45;
            --fd-teal-900: #0B2423;
            --fd-teal-850: #0F2F2E;
            --fd-teal-800: #123737;
            --fd-teal-700: #1C4B4A;
            --fd-teal-soft: rgba(18, 55, 55, 0.12);

            --fd-primary: var(--fd-mustard);
            --fd-primary-dark: #C49300;
            --fd-accent: var(--fd-teal-700);
            --fd-border: rgba(15, 23, 42, 0.14);
            --fd-border-strong: rgba(15, 23, 42, 0.22);
            --fd-white: #FFFFFF;
            --fd-black: #000000;
            --fd-bg: #F6F4EF;
            --fd-surface: rgba(255, 255, 255, 0.94);
        }

        body {
            padding-top: 56px;
            font-family: 'Manrope', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at 88% 8%, rgba(242, 194, 0, 0.16), transparent 38%),
                radial-gradient(circle at 8% 82%, rgba(18, 55, 55, 0.08), transparent 40%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, var(--fd-bg) 55%, rgba(243, 211, 154, 0.18) 100%);
            color: #212529;
        }

        h1, h2, h3, h4, h5, h6,
        .card-title,
        .navbar-brand {
            font-family: 'Sora', 'Manrope', sans-serif;
            letter-spacing: 0.01em;
        }

        .fd-shell {
            display: flex;
            min-height: calc(100vh - 56px);
        }

        body.role-admin,
        body.role-restaurant,
        body.role-default {
            --fd-accent-role: var(--fd-primary);
            --fd-accent-role-soft: var(--fd-mustard-soft);
        }

        .navbar-dashboard {
            background: linear-gradient(90deg, var(--fd-teal-900), var(--fd-teal-800), rgba(242, 194, 0, 0.6));
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.35);
        }

        .navbar-dashboard .navbar-brand,
        .navbar-dashboard .navbar-nav .nav-link,
        .navbar-dashboard .navbar-nav .nav-link:focus,
        .navbar-dashboard .navbar-nav .nav-link:hover {
            color: var(--fd-white) !important;
        }

        .navbar-dashboard .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.7);
            color: var(--fd-white);
        }

        .navbar-dashboard .btn-outline-light:hover {
            background-color: var(--fd-white);
            color: var(--fd-espresso);
        }

        .fd-sidebar {
            width: 250px;
            height: calc(100vh - 56px);
            position: sticky;
            top: 56px;
            left: 0;
            flex: 0 0 250px;
            overflow-y: auto;
            background: linear-gradient(180deg, var(--fd-teal-850), var(--fd-teal-800) 55%, var(--fd-teal-900) 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 2px 0 12px rgba(15, 23, 42, 0.22);
        }

        .fd-content {
            flex: 1;
            min-width: 0;
            margin-left: 0;
            padding: 1.5rem 1.75rem 2.5rem;
        }

        .fd-content h3.m-0 {
            font-weight: 800;
            color: var(--color-text-primary);
        }

        .fd-page-header {
            border: 1px solid rgba(58, 63, 69, 0.14);
            border-left: 4px solid var(--fd-primary);
            border-radius: .85rem;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.96), rgba(243, 211, 154, 0.14));
            padding: 1rem 1.25rem;
            color: var(--color-text-primary);
        }

        html[data-theme="dark"] .fd-page-header {
            border-color: rgba(255, 255, 255, 0.1);
            background: linear-gradient(120deg, rgba(26, 26, 26, 0.96), rgba(242, 194, 0, 0.08));
        }

        .fd-stat-card {
            border: 1px solid rgba(58, 63, 69, 0.16);
            border-radius: .75rem;
            background: rgba(255, 255, 255, 0.92);
            padding: .9rem 1rem;
            height: 100%;
            color: var(--color-text-primary);
        }

        html[data-theme="dark"] .fd-stat-card {
            background: rgba(26, 26, 26, 0.92);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .fd-stat-label {
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .72rem;
            color: #6B7280;
            display: block;
            margin-bottom: .2rem;
        }

        html[data-theme="dark"] .fd-stat-label {
            color: #B0B0B0;
        }

        .fd-stat-value {
            font-size: 1.45rem;
            margin: 0;
            color: #1F2937;
            font-weight: 800;
        }

        html[data-theme="dark"] .fd-stat-value {
            color: #FFFFFF;
        }

        @media (max-width: 991.98px) {
            .fd-sidebar {
                width: 0;
                flex-basis: 0;
                overflow: hidden;
                border-right: 0;
                box-shadow: none;
                transition: width .25s ease, flex-basis .25s ease;
            }

            .fd-sidebar.show {
                width: 220px;
                flex-basis: 220px;
                border-right: 1px solid var(--fd-border);
                box-shadow: 2px 0 8px rgba(15, 23, 42, 0.06);
            }

            .fd-content {
                padding-top: 1.25rem;
            }

            .fd-content .table {
                display: block;
                width: 100%;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        @media (max-width: 575.98px) {
            .fd-sidebar.show {
                width: 180px;
                flex-basis: 180px;
            }

            .fd-content {
                padding: 1rem .75rem 2rem;
            }
        }

        .fd-nav-link {
            display: flex;
            align-items: center;
            gap: .5rem;
            color: #FFFFFF !important;
            border-radius: .65rem;
            padding: .6rem .85rem;
            font-size: .9rem;
            transition: background-color .2s ease, color .2s ease, transform .2s ease, box-shadow .2s ease;
            text-decoration: none;
        }

        .fd-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.98);
        }

        .fd-nav-link.active {
            background: linear-gradient(90deg, rgba(242, 194, 0, 0.95), rgba(242, 194, 0, 0.72));
            color: #102423 !important;
            font-weight: 700;
            border-left: 0;
        }

        .fd-nav-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.16);
            flex: 0 0 34px;
        }

        .fd-nav-link.active .fd-nav-icon {
            background: rgba(16, 36, 35, 0.18);
            box-shadow: inset 0 0 0 1px rgba(16, 36, 35, 0.2);
        }

        .fd-nav-label {
            display: inline-block;
            line-height: 1.2;
        }

        .fd-sidebar small.text-muted {
            color: rgba(255, 255, 255, 0.6) !important;
            letter-spacing: .08em;
            font-size: .72rem;
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

        .btn-outline-secondary {
            color: #6B7280;
            border-color: #D1D5DB;
        }

        .btn-outline-dark {
            color: #6B7280;
            border-color: #D1D5DB;
        }

        .btn-outline-secondary:hover,
        .btn-outline-secondary:focus {
            background-color: #D1D5DB;
            color: #111827;
            border-color: #D1D5DB;
        }

        .btn-outline-dark:hover,
        .btn-outline-dark:focus {
            background-color: #D1D5DB;
            color: #111827;
            border-color: #D1D5DB;
        }

        .bg-light {
            background-color: rgba(255, 255, 255, 0.72) !important;
        }

        .page-item.active .page-link {
            background-color: var(--fd-primary);
            border-color: var(--fd-primary);
            color: var(--fd-espresso);
        }

        .page-link {
            color: var(--fd-primary-dark);
        }

        .page-link:hover {
            color: var(--fd-espresso);
        }

        .summary-card {
            border: 2px solid var(--fd-border-strong);
            border-top: 4px solid var(--fd-accent-role);
            border-radius: .85rem;
            background: linear-gradient(180deg, var(--fd-surface), rgba(243, 211, 154, 0.12));
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
        }

        .summary-card .card-body {
            padding: 1rem .75rem 1rem;
        }

        .card,
        .card.shadow-sm {
            border-radius: .9rem;
            border: 2px solid var(--fd-border-strong);
            background-color: var(--fd-surface);
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }

        .fd-content .card.border-0 {
            border: 2px solid var(--fd-border-strong) !important;
        }

        .fd-content .card .card-title {
            font-weight: 700;
            color: var(--fd-espresso);
        }

        .fd-content .card .card-body {
            border-top: 3px solid var(--fd-accent-role-soft);
        }

        .fd-content .table-responsive,
        .fd-content .modal-content,
        .fd-content .list-group,
        .fd-content .alert,
        .fd-content .form-control,
        .fd-content .form-select,
        .fd-content .input-group-text {
            border: 2px solid var(--fd-border);
            border-radius: .8rem;
            background-color: rgba(255, 255, 255, 0.92);
        }

        .fd-content .card .card-header,
        .fd-content .card .card-footer {
            border-color: var(--fd-border);
        }

        .table thead th {
            border-bottom-width: 1px;
        }

        .table-hover tbody tr:hover {
            background-color: #F9FAFB;
        }

        .fd-content .table {
            margin-bottom: 0;
        }

        .fd-content .table thead th {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #4B5563;
            background-color: #F8F9FA;
            border-bottom: 1px solid rgba(58, 63, 69, 0.16);
            white-space: nowrap;
        }

        .fd-content .table tbody td {
            border-color: rgba(58, 63, 69, 0.09);
        }

        .fd-content .btn,
        .fd-content .badge,
        .fd-content .form-control,
        .fd-content .form-select,
        .fd-content .table-hover tbody tr {
            transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, border-color .18s ease;
        }

        .fd-content .btn-outline-primary {
            border-width: 2px;
            border-color: var(--fd-accent-role);
            color: var(--fd-accent-role);
        }

        .fd-content .btn-outline-primary:hover,
        .fd-content .btn-outline-primary:focus {
            background-color: var(--fd-accent-role);
            border-color: var(--fd-accent-role);
            color: #fff;
        }

        .fd-content .btn {
            white-space: nowrap;
        }

        .fd-content .table td > .btn {
            margin-bottom: .2rem;
        }

        .fd-content .table td > .btn + .btn {
            margin-left: .3rem;
        }

        @media (hover: hover) and (pointer: fine) {
            .fd-nav-link:hover {
                transform: translateX(3px);
                box-shadow: inset 0 0 0 1px rgba(58, 63, 69, 0.14);
            }

            .fd-content .card.shadow-sm:hover {
                transform: translateY(-4px);
                box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
                border-color: rgba(36, 28, 12, 0.72);
            }

            .fd-content .summary-card:hover {
                border-top-color: rgba(242, 194, 0, 0.95);
                box-shadow: 0 16px 30px rgba(15, 23, 42, 0.14);
            }

            .fd-content .btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 14px rgba(15, 23, 42, 0.16);
            }

            .fd-content .table-hover tbody tr:hover {
                background-color: #F9FAFB;
                box-shadow: inset 2px 0 0 rgba(242, 194, 0, 0.85);
            }
        }

        @media (max-width: 767.98px) {
            .fd-content {
                padding: 1rem .85rem 2rem;
            }

            .fd-content .card .card-body {
                padding: .9rem;
            }

            .fd-content .table {
                font-size: .86rem;
            }

            .fd-content .table th,
            .fd-content .table td {
                padding: .6rem .5rem;
            }

            .fd-content .btn-sm {
                font-size: .78rem;
                padding: .35rem .55rem;
            }

            .fd-content .table td > .btn,
            .fd-content .table td > .btn + .btn {
                margin-left: 0;
                width: 100%;
            }

            .fd-content .row.mb-4 .col-12.d-flex.justify-content-between.align-items-center {
                flex-direction: column;
                align-items: flex-start !important;
                gap: .65rem;
            }

            .fd-content .row.mb-4 .col-12.d-flex.justify-content-between.align-items-center .btn {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .fd-nav-link,
            .card.shadow-sm,
            .fd-content .btn,
            .fd-content .badge,
            .fd-content .form-control,
            .fd-content .form-select,
            .fd-content .table-hover tbody tr {
                transition: none;
            }
        }

        /* Theme Toggle Button Styles */
        #theme-toggle-btn {
            font-size: 1.2rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            transition: all 0.08s linear;
        }

        #theme-toggle-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.9);
        }

        #theme-toggle-btn .theme-toggle-icon {
            display: inline-block;
            transition: none;
        }

        #theme-toggle-btn:active .theme-toggle-icon {
            transform: none;
        }

        /* Smooth transition for theme changes on all elements */
        html {
            transition: background-color 0.08s linear, color 0.08s linear;
        }

        body {
            transition: background 0.08s linear, color 0.08s linear;
        }
    </style>
    <?= $this->renderSection('head') ?>
</head>
<body class="<?= session('role') === 'admin' ? 'role-admin' : (session('role') === 'restaurant' ? 'role-restaurant' : 'role-default') ?>">

<nav class="navbar navbar-expand-lg navbar-dark navbar-dashboard fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= site_url('/') ?>">
            <strong>🏍️ FoodDash</strong>
        </a>

        <button class="btn btn-outline-light d-lg-none" id="fdSidebarToggle" type="button" aria-controls="fdSidebar" aria-expanded="false" aria-label="Toggle sidebar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item me-3 d-flex align-items-center text-white-50">
                    <small>Signed in as&nbsp;<strong><?= esc(session('name') ?: (session('email') ?? 'User')) ?></strong><?php if (session('role_name')): ?>&nbsp;·&nbsp;<span><?= esc(session('role_name')) ?></span><?php endif; ?></small>
                </li>
                <li class="nav-item ms-2">
                    <button type="button" id="theme-toggle-btn" class="btn btn-outline-light d-flex align-items-center gap-2" aria-pressed="false" aria-label="Toggle theme">
                        <span class="theme-toggle-icon">🌙</span>
                        <span class="theme-toggle-label d-none d-md-inline">Dark Mode</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="fd-shell">
<aside class="fd-sidebar" id="fdSidebar">
    <div class="p-3">
        <div class="mb-4">
            <small class="text-muted text-uppercase">Navigation</small>
        </div>

        <?php $role = session('role'); ?>

        <?php if ($role === 'admin'): ?>
            <!-- Admin Navigation -->
            <?php $sessionPermissions = session('permission_keys') ?? []; ?>
            <ul class="nav nav-pills flex-column gap-1">
                <?php if (in_array('access_admin_dashboard', $sessionPermissions, true)): ?>
                <li class="nav-item">
                    <a href="<?= site_url('dashboard/admin') ?>" class="nav-link fd-nav-link <?= (uri_string() === 'dashboard/admin') ? 'active' : '' ?>">
                        <span class="fd-nav-icon">🏠</span>
                        <span class="fd-nav-label">Dashboard</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php $activeTab = (string) (service('request')->getGet('tab') ?? ''); ?>
                <?php if (in_array('manage_roles', $sessionPermissions, true)): ?>
                    <li class="nav-item">
                        <a href="<?= site_url('admin/rbac?tab=roles') ?>" class="nav-link fd-nav-link <?= ($activeTab === 'roles') ? 'active' : '' ?>">
                            <span class="fd-nav-icon">🛡️</span>
                            <span class="fd-nav-label">Role Management</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (in_array('manage_staff_accounts', $sessionPermissions, true)): ?>
                    <li class="nav-item">
                        <a href="<?= site_url('admin/rbac?tab=users') ?>" class="nav-link fd-nav-link <?= ($activeTab === 'users') ? 'active' : '' ?>">
                            <span class="fd-nav-icon">👤</span>
                            <span class="fd-nav-label">User Management</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (in_array('manage_restaurant_information', $sessionPermissions, true)): ?>
                <li class="nav-item">
                    <a href="<?= site_url('admin/restaurants/pending') ?>" class="nav-link fd-nav-link <?= (str_contains(uri_string(), 'admin/restaurants')) ? 'active' : '' ?>">
                        <span class="fd-nav-icon">🏬</span>
                        <span class="fd-nav-label">Restaurant Approvals</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (in_array('manage_drivers', $sessionPermissions, true)): ?>
                <li class="nav-item">
                    <a href="<?= site_url('admin/drivers/pending') ?>" class="nav-link fd-nav-link <?= (str_contains(uri_string(), 'admin/drivers')) ? 'active' : '' ?>">
                        <span class="fd-nav-icon">🛵</span>
                        <span class="fd-nav-label">Driver Approvals</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (in_array('view_orders', $sessionPermissions, true)): ?>
                <li class="nav-item">
                    <a href="<?= site_url('dashboard/admin/orders/history') ?>" class="nav-link fd-nav-link <?= (str_contains(uri_string(), 'dashboard/admin/orders/history')) ? 'active' : '' ?>">
                        <span class="fd-nav-icon">📦</span>
                        <span class="fd-nav-label">Delivered History</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>

        <?php elseif ($role === 'restaurant'): ?>
            <!-- Restaurant Navigation -->
            <?php $sessionPermissions = session('permission_keys') ?? []; ?>
            <ul class="nav nav-pills flex-column gap-1">
                <?php if (in_array('access_admin_dashboard', $sessionPermissions, true) || true): // Restaurant dashboard is generally allowed if they have the role, but let's allow by default or we can leave it ?>
                <li class="nav-item">
                    <a href="<?= site_url('dashboard/restaurant') ?>" class="nav-link fd-nav-link <?= (uri_string() === 'dashboard/restaurant') ? 'active' : '' ?>">
                        <span class="fd-nav-icon">🏠</span>
                        <span class="fd-nav-label">Dashboard</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (in_array('manage_menu_items', $sessionPermissions, true)): ?>
                <li class="nav-item">
                    <a href="<?= site_url('menu') ?>" class="nav-link fd-nav-link <?= (str_contains(uri_string(), 'menu')) ? 'active' : '' ?>">
                        <span class="fd-nav-icon">🍔</span>
                        <span class="fd-nav-label">Menu</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (in_array('view_orders', $sessionPermissions, true)): ?>
                <li class="nav-item">
                    <a href="<?= site_url('orders') ?>" class="nav-link fd-nav-link <?= (uri_string() === 'orders') ? 'active' : '' ?>">
                        <span class="fd-nav-icon">🧾</span>
                        <span class="fd-nav-label">Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= site_url('orders/history') ?>" class="nav-link fd-nav-link <?= (str_contains(uri_string(), 'orders/history')) ? 'active' : '' ?>">
                        <span class="fd-nav-icon">📜</span>
                        <span class="fd-nav-label">Order History</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (in_array('manage_restaurant_information', $sessionPermissions, true)): ?>
                <li class="nav-item">
                    <a href="<?= site_url('settings') ?>" class="nav-link fd-nav-link <?= (str_contains(uri_string(), 'settings')) ? 'active' : '' ?>">
                        <span class="fd-nav-icon">⚙️</span>
                        <span class="fd-nav-label">Settings</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>

        <?php else: ?>
            <!-- Default Navigation -->
            <ul class="nav nav-pills flex-column gap-1">
                <li class="nav-item">
                    <a href="<?= site_url('dashboard/admin') ?>" class="nav-link fd-nav-link">
                        <span class="fd-nav-icon">🏠</span>
                        <span class="fd-nav-label">Dashboard</span>
                    </a>
                </li>
            </ul>
        <?php endif; ?>

        <!-- Logout Section -->
        <div class="mt-4 pt-4 border-top">
            <a href="<?= site_url('logout') ?>" class="btn btn-outline-danger btn-sm w-100">Logout</a>
        </div>
    </div>
</aside>

<main class="fd-content">
    <div class="container-fluid">
        <?= $this->renderSection('content') ?>
    </div>
</main>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('js/theme-manager-v3.js') ?>"></script>
<script>
    $(function () {
        // Sidebar toggle
        $('#fdSidebarToggle').on('click', function () {
            $('#fdSidebar').toggleClass('show');
            const expanded = $('#fdSidebar').hasClass('show');
            $(this).attr('aria-expanded', expanded ? 'true' : 'false');
        });

        $('#fdSidebar a').on('click', function () {
            if (window.innerWidth <= 991.98) {
                $('#fdSidebar').removeClass('show');
                $('#fdSidebarToggle').attr('aria-expanded', 'false');
            }
        });

        $(window).on('resize', function () {
            if (window.innerWidth > 991.98) {
                $('#fdSidebar').removeClass('show');
                $('#fdSidebarToggle').attr('aria-expanded', 'false');
            }
        });

        // Theme toggle handler - simplified and robust
        $('#theme-toggle-btn').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (window.themeManagerV3) {
                const newTheme = window.themeManagerV3.toggle();
                console.log('Theme toggled to:', newTheme);
            } else if (window.globalThemeManager) {
                window.globalThemeManager.toggle();
            }
        });

        // Listen for theme changes
        window.addEventListener('themechange', (e) => {
            console.log('themechange event received:', e.detail.theme);
            
            if (window.themeManagerV3) {
                window.themeManagerV3.updateButton(e.detail.theme);
            }
        });

        console.log('[Dashboard] jQuery ready - Theme manager available');
    });
</script>

<?= $this->renderSection('scripts') ?>

</body>
</html>
