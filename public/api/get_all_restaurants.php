<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$conn = db_conn();

$sql = 'SELECT id, name, address, logo, opening_hours, status, is_active, created_at, updated_at
    FROM restaurants
    WHERE is_active = 1 AND status = ?
    ORDER BY name ASC';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    json_error('Failed to prepare query', 500);
}

$status = 'approved';
$stmt->bind_param('s', $status);
$stmt->execute();
$result = $stmt->get_result();

$restaurants = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $row['is_active'] = (int) $row['is_active'];
    $restaurants[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'restaurants' => $restaurants,
]);
exit;
