<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\MenuItemModel;
use App\Models\RestaurantModel;

class MenuController extends ResourceController
{
    protected $format = 'json';
    protected $menuItemModel;
    protected $restaurantModel;

    public function __construct()
    {
        $this->menuItemModel = new MenuItemModel();
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
            ->where('is_available', 1)
            ->findAll();

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

        $query = $this->menuItemModel->where('is_available', 1);

        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        $menuItems = $query->findAll();

        // Add restaurant info to each item
        foreach ($menuItems as &$item) {
            $restaurant = $this->restaurantModel->find($item['restaurant_id']);
            $item['restaurant_name'] = $restaurant ? $restaurant['name'] : 'Unknown';
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
            ->where('is_available', 1)
            ->like('name', $query)
            ->orLike('description', $query)
            ->findAll();

        // Add restaurant info
        foreach ($menuItems as &$item) {
            $restaurant = $this->restaurantModel->find($item['restaurant_id']);
            $item['restaurant_name'] = $restaurant ? $restaurant['name'] : 'Unknown';
        }

        return $this->respond([
            'success' => true,
            'data'    => $menuItems
        ]);
    }
}
