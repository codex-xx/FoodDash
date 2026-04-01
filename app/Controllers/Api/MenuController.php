<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\MenuModel;
use App\Models\RestaurantModel;

class MenuController extends ResourceController
{
    protected $format = 'json';
    protected $menuItemModel;
    protected $restaurantModel;

    public function __construct()
    {
        $this->menuItemModel = new MenuModel();
        $this->restaurantModel = new RestaurantModel();
    }

    /**
     * Get all restaurants
     * GET /api/restaurants
     */
    public function restaurants()
    {
        $restaurants = $this->restaurantModel
            ->where('is_active', 1)
            ->where('status', 'approved')
            ->findAll();

        return $this->respond([
            'success' => true,
            'data'    => $restaurants
        ]);
    }

    /**
     * Get single restaurant details
     * GET /api/restaurants/:id
     */
    public function restaurant($id = null)
    {
        $restaurant = $this->restaurantModel->find($id);

        if (!$restaurant) {
            return $this->respond([
                'success' => false,
                'message' => 'Restaurant not found'
            ], 404);
        }

        // Get menu items for this restaurant
        $menuItems = $this->menuItemModel
            ->where('restaurant_id', $id)
            ->findAll();

        foreach ($menuItems as &$item) {
            $imagePath = $item['image_url'] ?? $item['image'] ?? null;
            $item['image_url'] = $this->toAbsoluteImageUrl($imagePath);
            $item['is_available'] = (int) ($item['availability'] ?? 1);
            $item['can_order'] = $item['is_available'] === 1;
            $item['ui_disabled'] = $item['is_available'] !== 1;
            $item['availability_message'] = $item['is_available'] === 1 ? null : 'Not available for a moment';
        }

        $restaurant['menu_items'] = $menuItems;

        return $this->respond([
            'success' => true,
            'data'    => $restaurant
        ]);
    }

    /**
     * Get all menu items (optionally filtered by restaurant)
     * GET /api/menu?restaurant_id=1
     */
    public function index()
    {
        $restaurantId = $this->request->getGet('restaurant_id');

        $query = $this->menuItemModel;

        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        $menuItems = $query->findAll();

        // Add restaurant info to each item
        foreach ($menuItems as &$item) {
            $restaurant = $this->restaurantModel->find($item['restaurant_id']);
            $item['restaurant_name'] = $restaurant ? $restaurant['name'] : 'Unknown';
            $imagePath = $item['image_url'] ?? $item['image'] ?? null;
            $item['image_url'] = $this->toAbsoluteImageUrl($imagePath);
            $item['is_available'] = (int) ($item['availability'] ?? 1);
            $item['can_order'] = $item['is_available'] === 1;
            $item['ui_disabled'] = $item['is_available'] !== 1;
            $item['availability_message'] = $item['is_available'] === 1 ? null : 'Not available for a moment';
        }

        return $this->respond([
            'success' => true,
            'data'    => $menuItems
        ]);
    }

    /**
     * Get single menu item
     * GET /api/menu/:id
     */
    public function show($id = null)
    {
        $menuItem = $this->menuItemModel->find($id);

        if (!$menuItem) {
            return $this->respond([
                'success' => false,
                'message' => 'Menu item not found'
            ], 404);
        }

        $restaurant = $this->restaurantModel->find($menuItem['restaurant_id']);
        $menuItem['restaurant'] = $restaurant;
        $imagePath = $menuItem['image_url'] ?? $menuItem['image'] ?? null;
        $menuItem['image_url'] = $this->toAbsoluteImageUrl($imagePath);
        $menuItem['is_available'] = (int) ($menuItem['availability'] ?? 1);
        $menuItem['can_order'] = $menuItem['is_available'] === 1;
        $menuItem['ui_disabled'] = $menuItem['is_available'] !== 1;
        $menuItem['availability_message'] = $menuItem['is_available'] === 1 ? null : 'Not available for a moment';

        return $this->respond([
            'success' => true,
            'data'    => $menuItem
        ]);
    }

    /**
     * Search menu items
     * GET /api/menu/search?q=pizza
     */
    public function search()
    {
        $query = $this->request->getGet('q');

        if (!$query || strlen($query) < 2) {
            return $this->respond([
                'success' => false,
                'message' => 'Search query must be at least 2 characters'
            ], 400);
        }

        $menuItems = $this->menuItemModel
            ->like('name', $query)
            ->orLike('description', $query)
            ->findAll();

        // Add restaurant info
        foreach ($menuItems as &$item) {
            $restaurant = $this->restaurantModel->find($item['restaurant_id']);
            $item['restaurant_name'] = $restaurant ? $restaurant['name'] : 'Unknown';
            $imagePath = $item['image_url'] ?? $item['image'] ?? null;
            $item['image_url'] = $this->toAbsoluteImageUrl($imagePath);
            $item['is_available'] = (int) ($item['availability'] ?? 1);
            $item['can_order'] = $item['is_available'] === 1;
            $item['ui_disabled'] = $item['is_available'] !== 1;
            $item['availability_message'] = $item['is_available'] === 1 ? null : 'Not available for a moment';
        }

        return $this->respond([
            'success' => true,
            'data'    => $menuItems
        ]);
    }

    private function toAbsoluteImageUrl(?string $imagePath): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        if (preg_match('#^https?://#i', $imagePath)) {
            return $imagePath;
        }

        return base_url(ltrim($imagePath, '/'));
    }
}
