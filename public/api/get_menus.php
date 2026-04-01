<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$restaurantIdRaw = $_GET['restaurant_id'] ?? '';
if ($restaurantIdRaw === '' || !ctype_digit((string) $restaurantIdRaw)) {
    json_error('restaurant_id is required and must be numeric', 422);
}

$restaurantId = (int) $restaurantIdRaw;
$conn = db_conn();

$sql = 'SELECT id, restaurant_id, name, description, price, image_url, category, availability, created_at, updated_at
    FROM menus
    WHERE restaurant_id = ?
    ORDER BY category ASC, name ASC';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    json_error('Failed to prepare query', 500);
}

$stmt->bind_param('i', $restaurantId);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $row['restaurant_id'] = (int) $row['restaurant_id'];
    $row['price'] = (float) $row['price'];
    $row['availability'] = (int) $row['availability'];
    $row['is_available'] = (int) $row['availability'];
    $row['can_order'] = ((int) $row['availability'] === 1);
    $row['ui_disabled'] = ((int) $row['availability'] !== 1);
    $row['availability_message'] = ((int) $row['availability'] === 1)
        ? null
        : 'Not available for a moment';
    $row['image_url'] = build_image_url($row['image_url']);
    $data[] = $row;
}

$stmt->close();
$conn->close();

json_success($data, 'Menus fetched successfully');
