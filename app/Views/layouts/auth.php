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
    <title><?= esc($pageTitle ?? 'FoodDash - Login') ?></title>
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Light Mode Auth */
            --auth-bg-primary: linear-gradient(180deg, #DFE8EE 0%, #E9EFF2 100%);
            --auth-bg-secondary: #FFFFFF;
            --auth-text-primary: #16202A;
            --auth-text-secondary: #3A4A59;
            --auth-border: rgba(22, 32, 42, 0.16);
            --auth-shadow: 0 28px 70px rgba(14, 24, 33, 0.28);
            --auth-ocean: #0F7A8A;
            --auth-ocean-deep: #075B67;
            --auth-amber: #E3A323;
            --auth-paper: #F5F8FA;
        }

        html[data-theme="dark"] {
            --auth-bg-primary: linear-gradient(180deg, #0F0F0F 0%, #1A1A1A 100%);
            --auth-bg-secondary: #1A1A1A;
            --auth-text-primary: #FFFFFF;
            --auth-text-secondary: #E0E0E0;
            --auth-border: rgba(255, 255, 255, 0.1);
            --auth-shadow: 0 28px 70px rgba(0, 0, 0, 0.8);
            --auth-ocean: #0F7A8A;
            --auth-ocean-deep: #075B67;
            --auth-amber: #E3A323;
            --auth-paper: #252525;
        }

        body.login-page {
            min-height: 100vh;
            margin: 0;
            font-family: 'Manrope', sans-serif;
            color: var(--auth-text-primary);
            position: relative;
            overflow-x: hidden;
            background: var(--auth-bg-primary);
            transition: background 0.3s ease, color 0.3s ease;
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
            background-color: var(--auth-paper);
<?php endif; ?>
        }

        .login-page-overlay {
            background: linear-gradient(125deg, rgba(10, 20, 30, 0.36), rgba(8, 38, 55, 0.3), rgba(10, 26, 38, 0.34));
            backdrop-filter: blur(0.5px);
            position: fixed;
            inset: 0;
            z-index: -1;
        }

        .form-container {
            background: var(--auth-bg-secondary);
            backdrop-filter: blur(10px);
            border: 1px solid var(--auth-border);
            border-radius: 1.25rem;
            padding: 2.5rem;
            box-shadow: var(--auth-shadow);
            transition: all 0.3s ease;
        }

        .form-title {
            color: var(--auth-text-primary);
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 1rem;
            transition: color 0.3s ease;
        }

        .form-subtitle {
            color: var(--auth-text-secondary);
            font-size: 0.95rem;
            margin-bottom: 2rem;
            transition: color 0.3s ease;
        }

        .form-label {
            color: var(--auth-text-primary);
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .form-control, .form-select {
            background-color: var(--auth-paper);
            color: var(--auth-text-primary);
            border: 1px solid var(--auth-border);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--auth-paper);
            color: var(--auth-text-primary);
            border-color: var(--auth-amber);
            box-shadow: 0 0 0 0.2rem rgba(227, 163, 35, 0.15);
        }

        .form-control::placeholder {
            color: var(--auth-text-secondary);
            opacity: 0.6;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--auth-ocean) 0%, var(--auth-ocean-deep) 100%);
            border: none;
            color: #FFFFFF;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(15, 122, 138, 0.2);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(15, 122, 138, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-link-text {
            color: var(--auth-ocean);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-link-text:hover {
            color: var(--auth-ocean-deep);
            text-decoration: underline;
        }

        .error-message {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid #dc3545;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        html[data-theme="dark"] .error-message {
            background-color: rgba(220, 53, 69, 0.2);
            border-color: #ff6b6b;
            color: #ff9999;
        }

        .success-message {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border: 1px solid #4CAF50;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        html[data-theme="dark"] .success-message {
            background-color: rgba(76, 175, 80, 0.2);
            border-color: #66BB6A;
            color: #81C784;
        }

        .auth-footer {
            color: var(--auth-text-secondary);
            font-size: 0.85rem;
            text-align: center;
            margin-top: 1.5rem;
            transition: color 0.3s ease;
        }

        .theme-toggle-auth {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--auth-border);
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        html[data-theme="dark"] .theme-toggle-auth {
            background: rgba(45, 40, 36, 0.9);
            border-color: var(--auth-border);
        }

        .theme-toggle-auth:hover {
            box-shadow: 0 4px 12px var(--auth-shadow);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 1.75rem;
            }

            .form-title {
                font-size: 1.5rem;
            }

            .theme-toggle-auth {
                top: 1rem;
                right: 1rem;
                font-size: 1rem;
                padding: 0.4rem 0.6rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
            }
        }
    </style>
    <?= $this->renderSection('head') ?>
</head>
<body class="login-page">
    <div class="login-page-overlay"></div>

    <!-- Theme Toggle for Auth Pages -->
    <button type="button" id="auth-theme-toggle-btn" class="theme-toggle-auth" aria-label="Toggle theme" title="Toggle Dark Mode">
        🌙
    </button>

    <?= $this->renderSection('content') ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('js/theme-manager-v3.js') ?>"></script>
    <script>
        $(function () {
            // Auth page theme toggle handler
            $('#auth-theme-toggle-btn').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (window.themeManagerV3) {
                    const newTheme = window.themeManagerV3.toggle();
                    console.log('Auth page theme toggled to:', newTheme);
                }
            });

            // Listen for theme changes
            window.addEventListener('themechange', (e) => {
                console.log('Auth page received theme change:', e.detail.theme);
                
                if (window.themeManagerV3) {
                    window.themeManagerV3.updateButton(e.detail.theme);
                }
            });

            console.log('[Auth Page] jQuery ready - Theme manager available');
        });
    </script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
