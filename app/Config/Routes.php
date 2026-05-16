<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Auth::login');

// Authentication
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->get('logout', 'Auth::logout');

// Password recovery
$routes->get('forgot', 'Auth::forgot');
$routes->post('forgot', 'Auth::sendReset');
$routes->get('reset/(:any)', 'Auth::reset/$1');
$routes->post('reset/(:any)', 'Auth::resetPassword/$1');

// Help Centre and Partner pages
$routes->get('help', 'Auth::help');
$routes->get('partner', 'Auth::partner');
$routes->post('partner/register', 'Auth::submitPartnerRegistration');

// Dashboards (protected by filter)
$routes->get('dashboard/admin', 'Dashboard::admin');
$routes->get('dashboard/restaurant', 'Dashboard::restaurant');
$routes->get('dashboard/admin/orders', 'Dashboard::adminOrdersHistory');
$routes->get('dashboard/admin/orders/history', 'Dashboard::adminOrdersHistory');

// Admin data endpoints
$routes->get('dashboard/admin/data', 'Dashboard::adminData');
$routes->get('dashboard/admin/security', 'Dashboard::adminSecurity');
$routes->get('dashboard/admin/security/data', 'Dashboard::adminSecurityData');
$routes->get('dashboard/admin/security/report', 'Dashboard::adminSecurityReport');
$routes->get('dashboard/admin/chart-data', 'Dashboard::adminChartData');
$routes->get('dashboard/admin/orders/data', 'Dashboard::adminOrdersData');
$routes->get('dashboard/admin/restaurant-locations', 'Dashboard::adminRestaurantLocations');
$routes->get('dashboard/restaurant/data', 'Dashboard::restaurantData');
$routes->get('dashboard/restaurant/chart-data', 'Dashboard::restaurantChartData');
$routes->get('dashboard/restaurant/location', 'Dashboard::restaurantLocation');
$routes->post('dashboard/restaurant/location', 'Dashboard::updateRestaurantLocation');
$routes->post('dashboard/order/(:num)/status', 'Dashboard::updateOrderStatus/$1');

// Menu Items (Restaurant)
$routes->get('menu', 'MenuItems::index');
$routes->get('menu/create', 'MenuItems::create');
$routes->post('menu/store', 'MenuItems::store');
$routes->get('menu/(:num)/edit', 'MenuItems::edit/$1');
$routes->post('menu/(:num)/update', 'MenuItems::update/$1');
$routes->post('menu/(:num)/delete', 'MenuItems::delete/$1');
$routes->post('menu/(:num)/restore', 'MenuItems::restore/$1');
$routes->post('menu/(:num)/toggle', 'MenuItems::toggleAvailability/$1');

// Orders
$routes->get('orders', 'Orders::restaurantOrders');
$routes->get('orders/history', 'Orders::orderHistory');
$routes->post('orders/(:num)/status', 'Orders::updateRestaurantOrderStatus/$1');
$routes->post('orders/(:num)/assign-driver', 'Orders::assignDriver/$1');
$routes->get('api/orders/daily-sales', 'Orders::getDailySales');

// Store Settings
$routes->get('settings', 'StoreSettings::index');
$routes->post('settings/update', 'StoreSettings::update');

// Admin Management
$routes->get('admin/users', 'AdminManagement::users');
$routes->post('admin/users/(:num)/suspend', 'AdminManagement::suspendUser/$1');
$routes->post('admin/users/(:num)/activate', 'AdminManagement::activateUser/$1');

// RBAC Management
$routes->get('admin/rbac', 'AdminRbac::index');
$routes->post('admin/rbac/roles/save', 'AdminRbac::saveRole');
$routes->post('admin/rbac/roles/delete/(:num)', 'AdminRbac::deleteRole/$1');
$routes->get('admin/rbac/roles/preview/(:num)', 'AdminRbac::previewRole/$1');
$routes->post('admin/rbac/users/save', 'AdminRbac::saveUser');
$routes->post('admin/rbac/users/toggle-status/(:num)', 'AdminRbac::toggleUserStatus/$1');
$routes->get('admin/rbac/users/preview/(:num)', 'AdminRbac::previewUser/$1');

$routes->get('admin/restaurants/pending', 'AdminManagement::pendingRestaurants');
$routes->post('admin/restaurants/(:num)/approve', 'AdminManagement::approveRestaurant/$1');
$routes->post('admin/restaurants/(:num)/reject', 'AdminManagement::rejectRestaurant/$1');

$routes->get('admin/drivers/pending', 'AdminManagement::pendingDrivers');
$routes->post('admin/drivers/(:num)/approve', 'AdminManagement::approveDriver/$1');
$routes->post('admin/drivers/(:num)/reject', 'AdminManagement::rejectDriver/$1');

$routes->get('api/admin/revenue-summary', 'AdminManagement::getRevenueSummary');

// ============================================
// MOBILE API ENDPOINTS
// ============================================

// Simple test endpoint (GET) - Use to test connectivity
$routes->get('api/test', static function () {
    return service('response')
        ->setHeader('Access-Control-Allow-Origin', '*')
        ->setHeader('Content-Type', 'application/json')
        ->setBody(json_encode([
            'success' => true,
            'message' => 'API is working!',
            'timestamp' => date('Y-m-d H:i:s')
        ]));
});

// CORS Preflight
$routes->options('api/(:any)', static function () {
    return service('response')
        ->setHeader('Access-Control-Allow-Origin', '*')
        ->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With')
        ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->setStatusCode(200);
});

// Auth (Public - No token required)
// Simple routes for Android (role-aware)
$routes->post('api/register', 'Api\AuthController::register');
$routes->post('api/register.php', 'Api\AuthController::register');
$routes->post('api/login', 'Api\AuthController::login');
$routes->post('api/login.php', 'Api\AuthController::login');

// Password Reset (Public - For Android App)
$routes->post('api/forgot-password', 'Api\AuthController::forgotPassword');
$routes->post('api/verify-reset-code', 'Api\AuthController::verifyResetCode');
$routes->post('api/verify-code', 'Api\AuthController::verifyResetCode');  // Alias for Android
$routes->post('api/reset-password', 'Api\AuthController::resetPassword');

// Full routes with user type
$routes->post('api/customer/register', 'Api\AuthController::customerRegister');
$routes->post('api/customer/login', 'Api\AuthController::customerLogin');
$routes->post('api/driver/register', 'Api\AuthController::driverRegister');
$routes->post('api/driver/login', 'Api\AuthController::driverLogin');
$routes->post('api/send-register-otp', 'Api\AuthController::sendRegisterOtpCode');
$routes->post('api/register/verify', 'Api\AuthController::verifyRegisterOtp');
$routes->post('api/verify-register-otp', 'Api\AuthController::verifyRegisterOtp');
$routes->post('api/verify-login-otp', 'Api\AuthController::verifyLoginOtp');
$routes->post('api/logout', 'Api\AuthController::logout');
$routes->post('api/logout.php', 'Api\AuthController::logout');
$routes->get('api/sessions', 'Api\SecurityController::sessions');
$routes->post('api/sessions/revoke-others', 'Api\SecurityController::revokeOtherSessions');
$routes->get('api/activity-logs', 'Api\SecurityController::activityLogs');

// Menu & Restaurants (Public)
$routes->get('api/restaurants', 'Api\MenuController::restaurants');
$routes->get('api/restaurants/(:num)', 'Api\MenuController::restaurant/$1');
$routes->get('api/restaurants-with-menus', 'Api\MenuController::restaurantsWithMenus');
$routes->get('api/restaurants/(:num)/menus', 'Api\MenuController::menusByRestaurant/$1');
$routes->get('api/menu', 'Api\MenuController::index');
$routes->get('api/menu/search', 'Api\MenuController::search');
$routes->get('api/menu/(:num)', 'Api\MenuController::show/$1');

// Centralized Menu Sync API
$routes->get('api/menus', 'Api\MenusController::index');
$routes->post('api/menus', 'Api\MenusController::create');
$routes->put('api/menus/(:num)', 'Api\MenusController::update/$1');
$routes->post('api/menus/(:num)', 'Api\MenusController::update/$1');
$routes->delete('api/menus/(:num)', 'Api\MenusController::delete/$1');

// Profile (Authenticated)
$routes->get('api/profile', 'Api\ProfileController::index');
$routes->put('api/profile', 'Api\ProfileController::update');
$routes->post('api/profile', 'Api\ProfileController::update');
$routes->post('api/fcm-token', 'Api\ProfileController::updateFcmToken');
$routes->post('api/driver/location', 'Api\ProfileController::updateLocation');

// Notifications (Authenticated - Customer)
$routes->get('api/notifications', 'Api\NotificationsController::index');
$routes->delete('api/notifications', 'Api\NotificationsController::clear');
$routes->post('api/notifications/clear', 'Api\NotificationsController::clear');
$routes->delete('api/notifications/(:num)', 'Api\NotificationsController::delete/$1');
$routes->post('api/notifications/(:num)/delete', 'Api\NotificationsController::delete/$1');

// Orders (Authenticated)
$routes->get('api/orders', 'Api\OrderController::index');
$routes->post('api/orders', 'Api\OrderController::create');
// Legacy mobile compatibility endpoints
$routes->post('api/place_order', 'Api\OrderController::create');
$routes->post('api/place_order.php', 'Api\OrderController::create');
$routes->get('api/orders/available', 'Api\OrderController::available');
$routes->get('api/orders/(:num)', 'Api\OrderController::show/$1');
$routes->put('api/orders/(:num)/status', 'Api\OrderController::updateStatus/$1');
$routes->post('api/orders/(:num)/status', 'Api\OrderController::updateStatus/$1');
$routes->post('api/orders/(:num)/accept', 'Api\OrderController::accept/$1');
$routes->post('api/orders/(:num)/cancel', 'Api\OrderController::cancel/$1');
$routes->post('api/update_status', 'Api\OrderController::updateStatusEndpoint');
$routes->get('api/update_status', 'Api\OrderController::updateStatusEndpoint');
$routes->post('api/update_order_status', 'Api\OrderController::updateStatusEndpoint');
$routes->get('api/update_order_status', 'Api\OrderController::updateStatusEndpoint');
$routes->post('api/update_order_status.php', 'Api\OrderController::updateStatusEndpoint');
$routes->get('api/update_order_status.php', 'Api\OrderController::updateStatusEndpoint');
$routes->post('api/assign_driver', 'Api\OrderController::assignDriverEndpoint');
$routes->get('api/orders/stream', 'Api\RealtimeController::orders');

// Database backup management (Admin Web App)
$routes->get('api/admin/backups', 'Api\AdminBackupController::index');
$routes->post('api/admin/backups/run', 'Api\AdminBackupController::runBackup');
$routes->post('api/admin/backups/restore', 'Api\AdminBackupController::restoreBackup');

// CRUD Business Transaction System (Admin API)
$routes->get('api/admin/business/users', 'Api\BusinessTransactionController::users');
$routes->post('api/admin/business/users', 'Api\BusinessTransactionController::createUser');
$routes->get('api/admin/business/users/(:num)', 'Api\BusinessTransactionController::showUser/$1');
$routes->put('api/admin/business/users/(:num)', 'Api\BusinessTransactionController::updateUser/$1');
$routes->post('api/admin/business/users/(:num)', 'Api\BusinessTransactionController::updateUser/$1');
$routes->delete('api/admin/business/users/(:num)', 'Api\BusinessTransactionController::deleteUser/$1');

$routes->get('api/admin/business/products', 'Api\BusinessTransactionController::products');
$routes->post('api/admin/business/products', 'Api\BusinessTransactionController::createProduct');
$routes->get('api/admin/business/products/(:num)', 'Api\BusinessTransactionController::showProduct/$1');
$routes->put('api/admin/business/products/(:num)', 'Api\BusinessTransactionController::updateProduct/$1');
$routes->post('api/admin/business/products/(:num)', 'Api\BusinessTransactionController::updateProduct/$1');
$routes->delete('api/admin/business/products/(:num)', 'Api\BusinessTransactionController::deleteProduct/$1');

$routes->get('api/admin/business/transactions', 'Api\BusinessTransactionController::transactions');
$routes->post('api/admin/business/transactions', 'Api\BusinessTransactionController::createTransaction');
$routes->get('api/admin/business/transactions/(:num)', 'Api\BusinessTransactionController::showTransaction/$1');
$routes->put('api/admin/business/transactions/(:num)', 'Api\BusinessTransactionController::updateTransaction/$1');
$routes->post('api/admin/business/transactions/(:num)', 'Api\BusinessTransactionController::updateTransaction/$1');
$routes->delete('api/admin/business/transactions/(:num)', 'Api\BusinessTransactionController::deleteTransaction/$1');

// Email Notification (Mobile)
$routes->post('api/send-notification-email', 'Api\EmailNotificationController::sendNotification');
$routes->get('api/test-email', 'Api\EmailNotificationController::testEmail');