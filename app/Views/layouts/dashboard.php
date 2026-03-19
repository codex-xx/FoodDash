<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($pageTitle ?? 'Dashboard - FoodDash') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            /* Palette from provided image */
            --fd-mustard: #F2C200;
            --fd-sand: #F3D39A;
            --fd-espresso: #241C0C;
            --fd-slate: #6B7C87;
            --fd-stone: #CFC6BA;
            --fd-charcoal: #3A3F45;

            --fd-primary: var(--fd-mustard);
            --fd-primary-dark: var(--fd-charcoal);
            --fd-accent: var(--fd-slate);
            --fd-border: rgba(58, 63, 69, 0.18);
            --fd-white: #FFFFFF;
            --fd-black: #000000;
            --fd-bg: #F6F3EE;
        }

        body {
            padding-top: 56px;
            background: linear-gradient(180deg, #FFFFFF 0%, var(--fd-bg) 55%, rgba(207, 198, 186, 0.55) 100%);
            color: #212529;
        }

        .navbar-dashboard {
            background: linear-gradient(90deg, var(--fd-espresso), var(--fd-charcoal));
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
            height: 100vh;
            position: fixed;
            top: 56px;
            left: 0;
            overflow-y: auto;
            background: linear-gradient(180deg, #FFFFFF, rgba(243, 211, 154, 0.35));
            border-right: 1px solid var(--fd-border);
            box-shadow: 2px 0 8px rgba(15, 23, 42, 0.06);
        }

        .fd-content {
            margin-left: 250px;
            padding: 1.5rem 1.75rem 2.5rem;
        }

        @media (max-width: 991.98px) {
            .fd-sidebar {
                left: -260px;
                transition: left .2s ease-in-out;
            }
            .fd-sidebar.show {
                left: 0;
            }
            .fd-content {
                margin-left: 0;
                padding-top: 1.25rem;
            }
        }

        .fd-nav-link {
            display: flex;
            align-items: center;
            gap: .5rem;
            color: rgba(36, 28, 12, 0.78);
            border-radius: .5rem;
            padding: .45rem .9rem;
            font-size: .9rem;
            transition: background-color .2s ease, color .2s ease, transform .2s ease, box-shadow .2s ease;
        }

        .fd-nav-link:hover {
            background-color: rgba(242, 194, 0, 0.18);
            color: var(--fd-espresso);
        }

        .fd-nav-link.active {
            background: rgba(36, 28, 12, 0.06);
            color: var(--fd-espresso) !important;
            font-weight: 600;
            border-left: 3px solid var(--fd-primary);
        }

        .fd-sidebar small.text-muted {
            color: rgba(58, 63, 69, 0.65) !important;
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

        .btn-outline-secondary:hover,
        .btn-outline-secondary:focus {
            background-color: #D1D5DB;
            color: #111827;
            border-color: #D1D5DB;
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
            border: 1px solid var(--fd-border);
            border-top-width: 3px;
            border-radius: .85rem;
            background: linear-gradient(180deg, #FFFFFF, rgba(243, 211, 154, 0.12));
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
        }

        .summary-card .card-body {
            padding: 1rem .75rem 1rem;
        }

        .card.shadow-sm {
            border-radius: .9rem;
            border: 1px solid var(--fd-border);
            background-color: rgba(255, 255, 255, 0.92);
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
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
                border-color: rgba(58, 63, 69, 0.28);
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
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-dashboard fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= site_url('/') ?>">
            <strong>🏍️ FoodDash</strong>
        </a>

        <button class="btn btn-outline-light d-lg-none" id="fdSidebarToggle" type="button">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item me-3 d-flex align-items-center text-white-50">
                    <small>Signed in as&nbsp;<strong><?= esc(session('email') ?? 'User') ?></strong></small>
                </li>
            </ul>
        </div>
    </div>
</nav>

<aside class="fd-sidebar" id="fdSidebar">
    <div class="p-3">
        <div class="mb-4">
            <small class="text-muted text-uppercase">Navigation</small>
        </div>

        <?php $role = session('role'); ?>

        <?php if ($role === 'admin'): ?>
            <!-- Admin Navigation -->
            <ul class="nav nav-pills flex-column gap-1">
                <li class="nav-item">
                    <a href="<?= site_url('dashboard/admin') ?>" class="nav-link fd-nav-link <?= (uri_string() === 'dashboard/admin') ? 'active' : '' ?>">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= site_url('admin/users') ?>" class="nav-link fd-nav-link <?= (str_contains(uri_string(), 'admin/users')) ? 'active' : '' ?>">
                        Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= site_url('admin/restaurants/pending') ?>" class="nav-link fd-nav-link <?= (str_contains(uri_string(), 'admin/restaurants')) ? 'active' : '' ?>">
                        Restaurant Approvals
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= site_url('admin/drivers/pending') ?>" class="nav-link fd-nav-link <?= (str_contains(uri_string(), 'admin/drivers')) ? 'active' : '' ?>">
                        Driver Approvals
                    </a>
                </li>
            </ul>

        <?php elseif ($role === 'restaurant'): ?>
            <!-- Restaurant Navigation -->
            <ul class="nav nav-pills flex-column gap-1">
                <li class="nav-item">
                    <a href="<?= site_url('dashboard/restaurant') ?>" class="nav-link fd-nav-link <?= (uri_string() === 'dashboard/restaurant') ? 'active' : '' ?>">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= site_url('menu') ?>" class="nav-link fd-nav-link <?= (str_contains(uri_string(), 'menu')) ? 'active' : '' ?>">
                        Menu
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= site_url('orders') ?>" class="nav-link fd-nav-link <?= (uri_string() === 'orders') ? 'active' : '' ?>">
                        Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= site_url('orders/history') ?>" class="nav-link fd-nav-link <?= (str_contains(uri_string(), 'orders/history')) ? 'active' : '' ?>">
                        Order History
                    </a>
                </li>
            </ul>

        <?php else: ?>
            <!-- Default Navigation -->
            <ul class="nav nav-pills flex-column gap-1">
                <li class="nav-item">
                    <a href="<?= site_url('dashboard/admin') ?>" class="nav-link fd-nav-link">
                        Dashboard
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(function () {
        $('#fdSidebarToggle').on('click', function () {
            $('#fdSidebar').toggleClass('show');
        });
    });
</script>

<?= $this->renderSection('scripts') ?>

</body>
</html>
