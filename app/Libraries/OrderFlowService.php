<?php

namespace App\Libraries;

use App\Models\DeliveryTypeModel;
use App\Models\DriverModel;
use App\Models\OrderModel;
use App\Models\OrderStatusLogModel;
use App\Models\RestaurantModel;

class OrderFlowService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_ARRIVED_RESTAURANT = 'arrived_at_restaurant';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    private OrderModel $orderModel;
    private DriverModel $driverModel;
    private DeliveryTypeModel $deliveryTypeModel;
    private OrderStatusLogModel $statusLogModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->driverModel = new DriverModel();
        $this->deliveryTypeModel = new DeliveryTypeModel();
        $this->statusLogModel = new OrderStatusLogModel();
    }

    public function validStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_PREPARING,
            self::STATUS_READY,
            self::STATUS_ARRIVED_RESTAURANT,
            self::STATUS_PICKED_UP,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ];
    }

    public function normalizeStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        $map = [
            'confirmed' => self::STATUS_ACCEPTED,
            'ready_for_pickup' => self::STATUS_READY,
            'picked_up' => self::STATUS_PICKED_UP,
            'arrived_at_restaurant' => self::STATUS_ARRIVED_RESTAURANT,
            'out_for_delivery' => self::STATUS_OUT_FOR_DELIVERY,
            'on_the_way' => self::STATUS_OUT_FOR_DELIVERY,
            'completed' => self::STATUS_DELIVERED,
        ];

        return $map[$normalized] ?? $normalized;
    }

    public function getSizeCategory(float $totalAmount): string
    {
        if ($totalAmount <= 300) {
            return 'small';
        }

        if ($totalAmount <= 900) {
            return 'medium';
        }

        return 'bulk';
    }

    public function getVehicleTypeForSize(string $sizeCategory): string
    {
        return match ($sizeCategory) {
            'small' => 'motorcycle',
            'medium' => 'tricycle',
            default => 'cab',
        };
    }

    public function resolveDeliveryTypeId(string $sizeCategory): ?int
    {
        $row = $this->deliveryTypeModel
            ->where('size_category', $sizeCategory)
            ->where('is_active', 1)
            ->first();

        return $row ? (int) $row['id'] : null;
    }

    public function calculateDistanceKm(?float $latitudeOne, ?float $longitudeOne, ?float $latitudeTwo, ?float $longitudeTwo): ?float
    {
        if ($latitudeOne === null || $longitudeOne === null || $latitudeTwo === null || $longitudeTwo === null) {
            return null;
        }

        $earthRadius = 6371;
        $latDelta = deg2rad($latitudeTwo - $latitudeOne);
        $lngDelta = deg2rad($longitudeTwo - $longitudeOne);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($latitudeOne)) * cos(deg2rad($latitudeTwo)) * sin($lngDelta / 2) ** 2;

        return 2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function getRestaurantDeliveryRadius(array $restaurant): ?float
    {
        $radius = $restaurant['delivery_radius_km'] ?? null;

        if (! is_numeric($radius)) {
            return null;
        }

        $radius = (float) $radius;

        return $radius > 0 ? $radius : null;
    }

    public function resolveRestaurantLocation(array $restaurant): array
    {
        $latitude = $restaurant['restaurant_latitude'] ?? $restaurant['latitude'] ?? null;
        $longitude = $restaurant['restaurant_longitude'] ?? $restaurant['longitude'] ?? null;

        return [
            'latitude' => is_numeric($latitude) ? (float) $latitude : null,
            'longitude' => is_numeric($longitude) ? (float) $longitude : null,
        ];
    }

    public function resolveDriverLocation(array $driver): array
    {
        $latitude = $driver['latitude'] ?? $driver['current_latitude'] ?? null;
        $longitude = $driver['longitude'] ?? $driver['current_longitude'] ?? null;

        return [
            'latitude' => is_numeric($latitude) ? (float) $latitude : null,
            'longitude' => is_numeric($longitude) ? (float) $longitude : null,
        ];
    }

    public function canDriverAcceptRestaurantOrder(array $driver, array $restaurant): array
    {
        $radius = $this->getRestaurantDeliveryRadius($restaurant);
        $restaurantLocation = $this->resolveRestaurantLocation($restaurant);
        $driverLocation = $this->resolveDriverLocation($driver);

        $hasLocationData = $radius !== null
            && $restaurantLocation['latitude'] !== null
            && $restaurantLocation['longitude'] !== null
            && $driverLocation['latitude'] !== null
            && $driverLocation['longitude'] !== null;

        if (! $hasLocationData) {
            return [
                'allowed' => true,
                'location_ready' => false,
                'distance_km' => null,
                'radius_km' => $radius,
                'reason' => null,
            ];
        }

        $distance = $this->calculateDistanceKm(
            $driverLocation['latitude'],
            $driverLocation['longitude'],
            $restaurantLocation['latitude'],
            $restaurantLocation['longitude']
        );

        return [
            'allowed' => $distance !== null && $distance <= $radius,
            'location_ready' => true,
            'distance_km' => $distance,
            'radius_km' => $radius,
            'reason' => $distance !== null && $distance > $radius
                ? 'Driver is outside the restaurant delivery radius.'
                : null,
        ];
    }

    public function getNearbyEligibleDriversForRestaurant(int $restaurantId, ?int $limit = 20): array
    {
        $restaurant = (new RestaurantModel())->find($restaurantId);
        if (! $restaurant) {
            return ['ok' => false, 'message' => 'Restaurant not found', 'code' => 404];
        }

        $radius = $this->getRestaurantDeliveryRadius($restaurant);
        $restaurantLocation = $this->resolveRestaurantLocation($restaurant);

        if ($radius === null || $restaurantLocation['latitude'] === null || $restaurantLocation['longitude'] === null) {
            return [
                'ok' => false,
                'message' => 'Restaurant location and delivery radius are required before fetching nearby riders.',
                'code' => 422,
            ];
        }

        $drivers = $this->driverModel
            ->select('id, user_id, name, phone, address, latitude, longitude, status, is_active, vehicle_type, license_number, updated_at')
            ->where('status', 'approved')
            ->where('is_active', 1)
            ->findAll();

        $eligibleDrivers = [];
        foreach ($drivers as $driver) {
            $eligibility = $this->canDriverAcceptRestaurantOrder($driver, $restaurant);
            if (! ($eligibility['location_ready'] ?? false) || ! ($eligibility['allowed'] ?? false)) {
                continue;
            }

            $eligibleDrivers[] = [
                'id' => (int) $driver['id'],
                'name' => $driver['name'] ?? '',
                'phone' => $driver['phone'] ?? null,
                'address' => $driver['address'] ?? null,
                'latitude' => isset($driver['latitude']) && is_numeric($driver['latitude']) ? (float) $driver['latitude'] : null,
                'longitude' => isset($driver['longitude']) && is_numeric($driver['longitude']) ? (float) $driver['longitude'] : null,
                'vehicle_type' => $driver['vehicle_type'] ?? null,
                'license_number' => $driver['license_number'] ?? null,
                'distance_km' => isset($eligibility['distance_km']) ? round((float) $eligibility['distance_km'], 2) : null,
            ];
        }

        usort($eligibleDrivers, static function (array $left, array $right): int {
            return ($left['distance_km'] ?? PHP_FLOAT_MAX) <=> ($right['distance_km'] ?? PHP_FLOAT_MAX);
        });

        if ($limit !== null && $limit > 0) {
            $eligibleDrivers = array_slice($eligibleDrivers, 0, $limit);
        }

        return [
            'ok' => true,
            'restaurant' => $restaurant,
            'delivery_radius_km' => $radius,
            'nearby_riders' => $eligibleDrivers,
        ];
    }

    public function updateStatus(int $orderId, string $toStatus, string $actorRole, ?int $actorId = null, ?string $notes = null): array
    {
        $order = $this->orderModel->find($orderId);
        if (! $order) {
            return ['ok' => false, 'message' => 'Order not found', 'code' => 404];
        }

        $normalizedTarget = $this->normalizeStatus($toStatus);
        if (! in_array($normalizedTarget, $this->validStatuses(), true)) {
            return ['ok' => false, 'message' => 'Invalid status', 'code' => 400];
        }

        $normalizedRole = strtolower(trim($actorRole));
        if ($normalizedRole === 'rider') {
            $normalizedRole = 'driver';
        }

        $allowedForRole = $this->allowedStatusesForRole($normalizedRole);
        if (! in_array($normalizedTarget, $allowedForRole, true)) {
            return [
                'ok' => false,
                'message' => 'Status is not allowed for role: ' . $normalizedRole,
                'code' => 403,
            ];
        }

        $fromStatus = $this->normalizeStatus((string) ($order['status'] ?? self::STATUS_PENDING));

        $updateData = [
            'status' => $normalizedTarget,
        ];

        $sizeCategory = $order['order_size_category'] ?? null;
        if (empty($sizeCategory)) {
            $sizeCategory = $this->getSizeCategory((float) ($order['total_amount'] ?? 0));
            $updateData['order_size_category'] = $sizeCategory;
            $updateData['delivery_type_id'] = $this->resolveDeliveryTypeId($sizeCategory);
        }

        // Keep order unassigned until a driver explicitly accepts it from mobile app.
        if ($normalizedRole === 'restaurant' && in_array($normalizedTarget, [self::STATUS_ACCEPTED, self::STATUS_PREPARING, self::STATUS_READY], true)) {
            if (empty($order['driver_id'])) {
                $updateData['driver_id'] = null;
            }
        }

        if ($normalizedRole === 'driver' && $actorId !== null && $actorId > 0) {
            $currentDriverId = (int) ($order['driver_id'] ?? 0);

            if ($currentDriverId > 0 && $currentDriverId !== $actorId) {
                return [
                    'ok' => false,
                    'message' => 'Order already assigned to another driver',
                    'code' => 409,
                ];
            }

            if ($currentDriverId === 0) {
                $updateData['driver_id'] = $actorId;
            }
        }

        $this->orderModel->update($orderId, $updateData);

        $this->statusLogModel->insert([
            'order_id' => $orderId,
            'from_status' => $fromStatus,
            'to_status' => $normalizedTarget,
            'changed_by_role' => $actorRole,
            'changed_by_id' => $actorId,
            'notes' => $notes,
        ]);

        return [
            'ok' => true,
            'order' => $this->orderModel->find($orderId),
            'message' => 'Order status updated',
        ];
    }

    private function allowedStatusesForRole(string $role): array
    {
        return match ($role) {
            'restaurant' => [
                self::STATUS_ACCEPTED,
                self::STATUS_PREPARING,
                self::STATUS_READY,
            ],
            'driver' => [
                self::STATUS_ACCEPTED,
                self::STATUS_PICKED_UP,
                self::STATUS_ARRIVED_RESTAURANT,
                self::STATUS_OUT_FOR_DELIVERY,
                self::STATUS_DELIVERED,
            ],
            'customer' => [
                self::STATUS_CANCELLED,
            ],
            default => $this->validStatuses(),
        };
    }

    public function manualAssignDriver(int $orderId, int $driverId, string $actorRole, ?int $actorId = null): array
    {
        $order = $this->orderModel->find($orderId);
        if (! $order) {
            return ['ok' => false, 'message' => 'Order not found', 'code' => 404];
        }

        $normalizedRole = strtolower(trim($actorRole));
        if ($normalizedRole !== 'admin') {
            return ['ok' => false, 'message' => 'Only admin can assign rider', 'code' => 403];
        }

        $currentStatus = $this->normalizeStatus((string) ($order['status'] ?? self::STATUS_PENDING));
        if ($currentStatus !== self::STATUS_PREPARING) {
            return [
                'ok' => false,
                'message' => 'Rider can only be assigned when order is in preparing status',
                'code' => 422,
            ];
        }

        $driver = $this->driverModel->find($driverId);
        if (! $driver || (int) ($driver['is_active'] ?? 0) !== 1 || ($driver['status'] ?? '') !== 'approved') {
            return ['ok' => false, 'message' => 'Driver is unavailable', 'code' => 422];
        }

        $restaurant = (new RestaurantModel())->find((int) ($order['restaurant_id'] ?? 0));
        if ($restaurant) {
            $eligibility = $this->canDriverAcceptRestaurantOrder($driver, $restaurant);
            if (($eligibility['location_ready'] ?? false) && ! ($eligibility['allowed'] ?? false)) {
                return [
                    'ok' => false,
                    'message' => $eligibility['reason'] ?? 'Driver is outside the restaurant delivery radius.',
                    'code' => 422,
                ];
            }
        }

        $this->orderModel->update($orderId, [
            'driver_id' => $driverId,
            'status' => self::STATUS_PICKED_UP,
        ]);

        $this->statusLogModel->insert([
            'order_id' => $orderId,
            'from_status' => $this->normalizeStatus((string) ($order['status'] ?? self::STATUS_PENDING)),
            'to_status' => self::STATUS_PICKED_UP,
            'changed_by_role' => $actorRole,
            'changed_by_id' => $actorId,
            'notes' => 'Driver #' . $driverId . ' picked up the order',
        ]);

        return [
            'ok' => true,
            'order' => $this->orderModel->find($orderId),
            'message' => 'Driver assigned',
        ];
    }

    public function acceptOrder(int $orderId, int $driverId, ?string $notes = null): array
    {
        $order = $this->orderModel->find($orderId);
        if (! $order) {
            return ['ok' => false, 'message' => 'Order not found', 'code' => 404];
        }

        $currentStatus = $this->normalizeStatus((string) ($order['status'] ?? self::STATUS_PENDING));
        if (! in_array($currentStatus, [self::STATUS_ACCEPTED, self::STATUS_PREPARING, self::STATUS_READY], true)) {
            return ['ok' => false, 'message' => 'Order cannot be accepted in current status', 'code' => 400];
        }

        $currentDriverId = (int) ($order['driver_id'] ?? 0);
        if ($currentDriverId > 0 && $currentDriverId !== $driverId) {
            return ['ok' => false, 'message' => 'Order already assigned to another driver', 'code' => 409];
        }

        $driver = $this->driverModel->find($driverId);
        $restaurant = (new RestaurantModel())->find((int) ($order['restaurant_id'] ?? 0));

        if ($driver && $restaurant) {
            $eligibility = $this->canDriverAcceptRestaurantOrder($driver, $restaurant);
            if (($eligibility['location_ready'] ?? false) && ! ($eligibility['allowed'] ?? false)) {
                return [
                    'ok' => false,
                    'message' => $eligibility['reason'] ?? 'Driver is outside the restaurant delivery radius.',
                    'code' => 422,
                ];
            }
        }

        if ($currentDriverId === 0) {
            $this->orderModel->update($orderId, ['driver_id' => $driverId]);
        }

        $this->statusLogModel->insert([
            'order_id' => $orderId,
            'from_status' => $currentStatus,
            'to_status' => $currentStatus,
            'changed_by_role' => 'driver',
            'changed_by_id' => $driverId,
            'notes' => $notes ?? 'Driver accepted incoming request',
        ]);

        return [
            'ok' => true,
            'order' => $this->orderModel->find($orderId),
            'message' => 'Order accepted',
        ];
    }

    private function autoAssignDriver(array $order, string $sizeCategory): ?int
    {
        $vehicleType = $this->getVehicleTypeForSize($sizeCategory);

        $driver = $this->driverModel
            ->where('status', 'approved')
            ->where('is_active', 1)
            ->where('vehicle_type', $vehicleType)
            ->orderBy('updated_at', 'ASC')
            ->first();

        if (! $driver) {
            return null;
        }

        return (int) $driver['id'];
    }
}
