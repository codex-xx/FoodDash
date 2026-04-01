<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

require_write_access();

$restaurantIdRaw = $_POST['restaurant_id'] ?? '';
$name = trim((string) ($_POST['name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$priceRaw = $_POST['price'] ?? null;
$category = trim((string) ($_POST['category'] ?? ''));
$availabilityRaw = $_POST['availability'] ?? 1;

$errors = [];
if ($restaurantIdRaw === '' || !ctype_digit((string) $restaurantIdRaw)) {
    $errors['restaurant_id'] = 'restaurant_id is required and must be numeric';
}
if ($name === '' || strlen($name) < 2) {
    $errors['name'] = 'name is required and must be at least 2 characters';
}
if (!validate_decimal($priceRaw)) {
    $errors['price'] = 'price must be a valid number >= 0';
}
if (!in_array((string) $availabilityRaw, ['0', '1'], true)) {
    $errors['availability'] = 'availability must be 0 or 1';
}

if (!empty($errors)) {
    json_error('Validation failed', 422, $errors);
}

$restaurantId = (int) $restaurantIdRaw;
if (!can_write_restaurant($restaurantId)) {
    json_error('You are not allowed to modify this restaurant menu', 403);
}

$imagePath = upload_menu_image('image');
if ($imagePath === null) {
    $imagePath = trim((string) ($_POST['image_url'] ?? '')) ?: null;
}

$conn = db_conn();
$sql = 'INSERT INTO menus (restaurant_id, name, description, price, image_url, category, availability, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    json_error('Failed to prepare insert', 500);
}

$price = (float) $priceRaw;
$availability = (int) $availabilityRaw;
$stmt->bind_param('issdssi', $restaurantId, $name, $description, $price, $imagePath, $category, $availability);

if (!$stmt->execute()) {
    json_error('Failed to add menu item', 500);
}

$newId = (int) $conn->insert_id;
$stmt->close();
$conn->close();

json_success([
    [
        'id' => $newId,
        'restaurant_id' => $restaurantId,
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'image_url' => build_image_url($imagePath),
        'category' => $category,
        'availability' => $availability,
        'is_available' => $availability,
    ],
], 'Menu item added successfully');
