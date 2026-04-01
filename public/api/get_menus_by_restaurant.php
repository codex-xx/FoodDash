<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$restaurantIdRaw = $_GET['restaurant_id'] ?? '';
if ($restaurantIdRaw === '' || !ctype_digit((string) $restaurantIdRaw)) {
    json_error('restaurant_id is required and must be numeric', 422);
}

$restaurantId = (int) $restaurantIdRaw;
$conn = db_conn();

$restaurantStmt = $conn->prepare('SELECT id, name, address FROM restaurants WHERE id = ? AND is_active = 1 AND status = ? LIMIT 1');
if (!$restaurantStmt) {
    json_error('Failed to prepare restaurant query', 500);
}

$status = 'approved';
$restaurantStmt->bind_param('is', $restaurantId, $status);
$restaurantStmt->execute();
$restaurant = $restaurantStmt->get_result()->fetch_assoc();
$restaurantStmt->close();

if (!$restaurant) {
    $conn->close();
    json_error('Restaurant not found', 404);
}

$menuStmt = $conn->prepare('SELECT id, restaurant_id, name, description, price, image_url, category, availability, created_at, updated_at FROM menus WHERE restaurant_id = ? ORDER BY category ASC, name ASC');
if (!$menuStmt) {
    json_error('Failed to prepare menu query', 500);
}

$menuStmt->bind_param('i', $restaurantId);
$menuStmt->execute();
$menuResult = $menuStmt->get_result();

$menus = [];
while ($row = $menuResult->fetch_assoc()) {
    $availability = (int) $row['availability'];

    $menus[] = [
        'id' => (int) $row['id'],
        'restaurant_id' => (int) $row['restaurant_id'],
        'name' => $row['name'],
        'description' => $row['description'],
        'price' => (float) $row['price'],
        'image_url' => build_image_url($row['image_url']),
        'category' => $row['category'],
        'availability' => $availability,
        'is_available' => $availability,
        'can_order' => $availability === 1,
        'ui_disabled' => $availability !== 1,
        'availability_message' => $availability === 1 ? null : 'Not available for a moment',
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
    ];
}

$menuStmt->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'restaurant' => [
        'id' => (int) $restaurant['id'],
        'name' => $restaurant['name'],
        'address' => $restaurant['address'],
    ],
    'menus' => $menus,
]);
exit;
