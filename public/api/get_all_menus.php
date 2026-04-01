<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$conn = db_conn();

$sql = 'SELECT
        r.id AS restaurant_id,
        r.name AS restaurant_name,
        r.address AS restaurant_address,
        m.id AS menu_id,
        m.name AS menu_name,
        m.description AS menu_description,
        m.price AS menu_price,
        m.image_url AS menu_image_url,
        m.category AS menu_category,
        m.availability AS menu_availability,
        m.created_at AS menu_created_at,
        m.updated_at AS menu_updated_at
    FROM restaurants r
    LEFT JOIN menus m ON m.restaurant_id = r.id
    WHERE r.is_active = 1 AND r.status = ?
    ORDER BY r.name ASC, m.category ASC, m.name ASC';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    json_error('Failed to prepare query', 500);
}

$status = 'approved';
$stmt->bind_param('s', $status);
$stmt->execute();
$result = $stmt->get_result();

$restaurantsMap = [];
while ($row = $result->fetch_assoc()) {
    $restaurantId = (int) $row['restaurant_id'];

    if (!isset($restaurantsMap[$restaurantId])) {
        $restaurantsMap[$restaurantId] = [
            'id' => $restaurantId,
            'name' => $row['restaurant_name'],
            'address' => $row['restaurant_address'],
            'menus' => [],
        ];
    }

    if ($row['menu_id'] === null) {
        continue;
    }

    $availability = (int) $row['menu_availability'];

    $restaurantsMap[$restaurantId]['menus'][] = [
        'id' => (int) $row['menu_id'],
        'restaurant_id' => $restaurantId,
        'name' => $row['menu_name'],
        'description' => $row['menu_description'],
        'price' => (float) $row['menu_price'],
        'image_url' => build_image_url($row['menu_image_url']),
        'category' => $row['menu_category'],
        'availability' => $availability,
        'is_available' => $availability,
        'can_order' => $availability === 1,
        'ui_disabled' => $availability !== 1,
        'availability_message' => $availability === 1 ? null : 'Not available for a moment',
        'created_at' => $row['menu_created_at'],
        'updated_at' => $row['menu_updated_at'],
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'restaurants' => array_values($restaurantsMap),
]);
exit;
