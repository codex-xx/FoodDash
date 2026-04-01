<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

require_write_access();

$idRaw = $_POST['id'] ?? '';
if ($idRaw === '' || !ctype_digit((string) $idRaw)) {
    json_error('id is required and must be numeric', 422);
}

$id = (int) $idRaw;
$conn = db_conn();

$checkStmt = $conn->prepare('SELECT restaurant_id, image_url FROM menus WHERE id = ? LIMIT 1');
if (!$checkStmt) {
    json_error('Failed to prepare lookup', 500);
}
$checkStmt->bind_param('i', $id);
$checkStmt->execute();
$menu = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if (!$menu) {
    $conn->close();
    json_error('Menu item not found', 404);
}

$restaurantId = (int) $menu['restaurant_id'];
if (!can_write_restaurant($restaurantId)) {
    $conn->close();
    json_error('You are not allowed to modify this menu item', 403);
}

$name = isset($_POST['name']) ? trim((string) $_POST['name']) : null;
$description = isset($_POST['description']) ? trim((string) $_POST['description']) : null;
$priceRaw = $_POST['price'] ?? null;
$category = isset($_POST['category']) ? trim((string) $_POST['category']) : null;
$availabilityRaw = $_POST['availability'] ?? null;
$imagePath = upload_menu_image('image');

$fields = [];
$params = [];
$types = '';

if ($name !== null) {
    if ($name === '' || strlen($name) < 2) {
        $conn->close();
        json_error('name must be at least 2 characters', 422);
    }
    $fields[] = 'name = ?';
    $types .= 's';
    $params[] = $name;
}

if ($description !== null) {
    $fields[] = 'description = ?';
    $types .= 's';
    $params[] = $description;
}

if ($priceRaw !== null && $priceRaw !== '') {
    if (!validate_decimal($priceRaw)) {
        $conn->close();
        json_error('price must be a valid number >= 0', 422);
    }
    $fields[] = 'price = ?';
    $types .= 'd';
    $params[] = (float) $priceRaw;
}

if ($category !== null) {
    $fields[] = 'category = ?';
    $types .= 's';
    $params[] = $category;
}

if ($availabilityRaw !== null && $availabilityRaw !== '') {
    if (!in_array((string) $availabilityRaw, ['0', '1'], true)) {
        $conn->close();
        json_error('availability must be 0 or 1', 422);
    }
    $fields[] = 'availability = ?';
    $types .= 'i';
    $params[] = (int) $availabilityRaw;
}

if ($imagePath !== null) {
    $fields[] = 'image_url = ?';
    $types .= 's';
    $params[] = $imagePath;
} elseif (isset($_POST['image_url'])) {
    $manualImage = trim((string) $_POST['image_url']);
    $fields[] = 'image_url = ?';
    $types .= 's';
    $params[] = $manualImage;
    $imagePath = $manualImage;
} else {
    $imagePath = $menu['image_url'] ?? null;
}

if (empty($fields)) {
    $conn->close();
    json_error('No fields provided for update', 422);
}

$fields[] = 'updated_at = NOW()';
$sql = 'UPDATE menus SET ' . implode(', ', $fields) . ' WHERE id = ?';
$types .= 'i';
$params[] = $id;

$updateStmt = $conn->prepare($sql);
if (!$updateStmt) {
    $conn->close();
    json_error('Failed to prepare update', 500);
}

bind_dynamic_params($updateStmt, $types, $params);
if (!$updateStmt->execute()) {
    $updateStmt->close();
    $conn->close();
    json_error('Failed to update menu item', 500);
}

$updateStmt->close();

$getStmt = $conn->prepare('SELECT id, restaurant_id, name, description, price, image_url, category, availability, created_at, updated_at FROM menus WHERE id = ? LIMIT 1');
$getStmt->bind_param('i', $id);
$getStmt->execute();
$row = $getStmt->get_result()->fetch_assoc();
$getStmt->close();
$conn->close();

if ($row) {
    $row['id'] = (int) $row['id'];
    $row['restaurant_id'] = (int) $row['restaurant_id'];
    $row['price'] = (float) $row['price'];
    $row['availability'] = (int) $row['availability'];
    $row['is_available'] = (int) $row['availability'];
    $row['image_url'] = build_image_url($row['image_url']);
}

json_success($row ? [$row] : [], 'Menu item updated successfully');
