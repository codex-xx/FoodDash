<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/deployment_env.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function db_conn(): mysqli
{
    try {
        $conn = fooddash_db_connection();
    } catch (RuntimeException $exception) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed',
        ]);
        exit;
    }

    if ($conn->connect_errno) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed',
        ]);
        exit;
    }

    return $conn;
}

function json_success(array $data = [], string $message = 'OK'): void
{
    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'data' => $data,
    ]);
    exit;
}

function json_error(string $message, int $statusCode = 400, array $errors = []): void
{
    http_response_code($statusCode);
    echo json_encode([
        'status' => 'error',
        'message' => $message,
        'errors' => $errors,
    ]);
    exit;
}

function require_write_access(): void
{
    $isLoggedIn = !empty($_SESSION['isLoggedIn']);
    $role = $_SESSION['role'] ?? '';

    if (! $isLoggedIn || ! in_array($role, ['restaurant', 'admin'], true)) {
        json_error('Only restaurant/admin can modify menu', 403);
    }
}

function can_write_restaurant(int $restaurantId): bool
{
    $role = $_SESSION['role'] ?? '';

    if ($role === 'admin') {
        return true;
    }

    $sessionRestaurantId = (int) ($_SESSION['restaurant_id'] ?? 0);

    return $role === 'restaurant' && $sessionRestaurantId === $restaurantId;
}

function validate_decimal($value): bool
{
    return is_numeric($value) && (float) $value >= 0;
}

function build_image_url(?string $path): ?string
{
    if (empty($path)) {
        return null;
    }

    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api')), '/');

    // /api -> project public root
    $root = preg_replace('#/api$#', '', $basePath);

    return $scheme . '://' . $host . $root . '/' . ltrim($path, '/');
}

function upload_menu_image(string $fieldName = 'image'): ?string
{
    if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return null;
    }

    if ((int) ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int) ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        json_error('Image upload failed', 400);
    }

    $tmpName = $_FILES[$fieldName]['tmp_name'] ?? '';
    if (!is_uploaded_file($tmpName)) {
        json_error('Invalid upload', 400);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmpName) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        json_error('Invalid image type. Allowed: jpg, png, webp, gif', 400);
    }

    $uploadDirFs = dirname(__DIR__) . '/uploads/menu';
    if (!is_dir($uploadDirFs) && !mkdir($uploadDirFs, 0755, true) && !is_dir($uploadDirFs)) {
        json_error('Failed to create upload folder', 500);
    }

    $fileName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $dest = $uploadDirFs . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $dest)) {
        json_error('Failed to move uploaded image', 500);
    }

    return 'uploads/menu/' . $fileName;
}

function bind_dynamic_params(mysqli_stmt $stmt, string $types, array &$params): void
{
    $bindArgs = [];
    $bindArgs[] = $types;

    foreach ($params as $key => &$value) {
        $bindArgs[] = &$params[$key];
    }

    if (!call_user_func_array([$stmt, 'bind_param'], $bindArgs)) {
        json_error('Failed to bind statement parameters', 500);
    }
}

function canonical_order_statuses(): array
{
    return ['pending', 'accepted', 'preparing', 'ready', 'picked_up', 'arrived_at_restaurant', 'out_for_delivery', 'delivered', 'cancelled'];
}

function normalize_order_status(string $status): string
{
    $normalized = strtolower(trim($status));

    $aliases = [
        'confirmed' => 'accepted',
        'ready_for_pickup' => 'ready',
        'picked_up' => 'picked_up',
        'arrived_at_restaurant' => 'arrived_at_restaurant',
        'out_for_delivery' => 'out_for_delivery',
        'on_the_way' => 'out_for_delivery',
        'completed' => 'delivered',
    ];

    return $aliases[$normalized] ?? $normalized;
}

function get_size_category(float $totalAmount): string
{
    if ($totalAmount <= 300) {
        return 'small';
    }

    if ($totalAmount <= 900) {
        return 'medium';
    }

    return 'bulk';
}

function recommended_vehicle_type(string $sizeCategory): string
{
    if ($sizeCategory === 'small') {
        return 'motorcycle';
    }

    if ($sizeCategory === 'medium') {
        return 'tricycle';
    }

    return 'cab';
}
