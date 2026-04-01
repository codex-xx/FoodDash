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

$checkStmt = $conn->prepare('SELECT restaurant_id FROM menus WHERE id = ? LIMIT 1');
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
    json_error('You are not allowed to delete this menu item', 403);
}

$deleteStmt = $conn->prepare('DELETE FROM menus WHERE id = ?');
if (!$deleteStmt) {
    $conn->close();
    json_error('Failed to prepare delete', 500);
}

$deleteStmt->bind_param('i', $id);
if (!$deleteStmt->execute()) {
    $deleteStmt->close();
    $conn->close();
    json_error('Failed to delete menu item', 500);
}

$deleteStmt->close();
$conn->close();

json_success([], 'Menu item deleted successfully');
