<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$queryRaw = trim((string) ($_GET['query'] ?? ''));
$categoryRaw = trim((string) ($_GET['category'] ?? ''));
$minPriceRaw = $_GET['min_price'] ?? null;
$maxPriceRaw = $_GET['max_price'] ?? null;
$sortRaw = strtolower(trim((string) ($_GET['sort'] ?? '')));

if ($queryRaw === '') {
    echo json_encode([
        'status' => 'success',
        'restaurants' => [],
        'menus' => [],
    ]);
    exit;
}

if ($minPriceRaw !== null && $minPriceRaw !== '' && !validate_decimal($minPriceRaw)) {
    json_error('min_price must be a non-negative number', 422);
}

if ($maxPriceRaw !== null && $maxPriceRaw !== '' && !validate_decimal($maxPriceRaw)) {
    json_error('max_price must be a non-negative number', 422);
}

$minPrice = ($minPriceRaw !== null && $minPriceRaw !== '') ? (float) $minPriceRaw : null;
$maxPrice = ($maxPriceRaw !== null && $maxPriceRaw !== '') ? (float) $maxPriceRaw : null;

if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
    json_error('min_price cannot be greater than max_price', 422);
}

$allowedSort = [
    'cheapest' => 'm.price ASC, m.name ASC',
    'expensive' => 'm.price DESC, m.name ASC',
    'newest' => 'm.created_at DESC, m.name ASC',
    'name' => 'm.name ASC',
];

$menuOrderBy = $allowedSort[$sortRaw] ?? 'm.name ASC';
$like = '%' . $queryRaw . '%';
$approvedStatus = 'approved';

$conn = db_conn();

$restaurantSql = 'SELECT DISTINCT r.id, r.name, r.logo AS image, COALESCE(r.is_open, 1) AS is_open
    FROM restaurants r
    LEFT JOIN menus m ON m.restaurant_id = r.id
    WHERE r.is_active = 1
      AND r.status = ?
            AND COALESCE(r.is_open, 1) = 1
      AND (
            r.name LIKE ?
            OR m.name LIKE ?
            OR m.description LIKE ?
            OR m.category LIKE ?
      )
    ORDER BY r.name ASC';

$restaurantStmt = $conn->prepare($restaurantSql);
if (!$restaurantStmt) {
    json_error('Failed to prepare restaurant search query', 500);
}

$restaurantStmt->bind_param('sssss', $approvedStatus, $like, $like, $like, $like);
$restaurantStmt->execute();
$restaurantResult = $restaurantStmt->get_result();

$restaurants = [];
while ($row = $restaurantResult->fetch_assoc()) {
    $restaurants[] = [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'image' => build_image_url($row['image']),
        'is_open' => (int) $row['is_open'],
    ];
}

$restaurantStmt->close();

$menuSql = 'SELECT
        m.id,
        m.name,
        m.description,
        m.category,
        m.price,
        m.image_url,
        r.name AS restaurant_name,
                r.id AS restaurant_id,
                COALESCE(r.is_open, 1) AS restaurant_is_open
    FROM menus m
    INNER JOIN restaurants r ON r.id = m.restaurant_id
    WHERE r.is_active = 1
      AND r.status = ?
            AND COALESCE(r.is_open, 1) = 1
      AND (
            r.name LIKE ?
            OR m.name LIKE ?
            OR m.description LIKE ?
            OR m.category LIKE ?
      )';

$menuTypes = 'sssss';
$menuParams = [$approvedStatus, $like, $like, $like, $like];

if ($categoryRaw !== '') {
    $menuSql .= ' AND m.category = ?';
    $menuTypes .= 's';
    $menuParams[] = $categoryRaw;
}

if ($minPrice !== null) {
    $menuSql .= ' AND m.price >= ?';
    $menuTypes .= 'd';
    $menuParams[] = $minPrice;
}

if ($maxPrice !== null) {
    $menuSql .= ' AND m.price <= ?';
    $menuTypes .= 'd';
    $menuParams[] = $maxPrice;
}

$menuSql .= ' ORDER BY ' . $menuOrderBy;

$menuStmt = $conn->prepare($menuSql);
if (!$menuStmt) {
    json_error('Failed to prepare menu search query', 500);
}

bind_dynamic_params($menuStmt, $menuTypes, $menuParams);
$menuStmt->execute();
$menuResult = $menuStmt->get_result();

$menus = [];
while ($row = $menuResult->fetch_assoc()) {
    $menus[] = [
        'id' => (int) $row['id'],
        'restaurant_id' => (int) $row['restaurant_id'],
        'name' => $row['name'],
        'description' => $row['description'],
        'category' => $row['category'],
        'restaurant_name' => $row['restaurant_name'],
        'restaurant_is_open' => (int) $row['restaurant_is_open'],
        'price' => (float) $row['price'],
        'image_url' => build_image_url($row['image_url']),
    ];
}

$menuStmt->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'restaurants' => $restaurants,
    'menus' => $menus,
]);
exit;
