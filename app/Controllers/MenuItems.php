<?php

namespace App\Controllers;

use App\Models\MenuItemModel;

class MenuItems extends BaseController
{
    protected $menuItemModel;

    public function __construct()
    {
        $this->menuItemModel = new MenuItemModel();
    }

    /**
     * Display all menu items for the logged-in restaurant
     */
    public function index()
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return redirect()->to('/login')->with('error', 'Unauthorized');
        }

        $restaurantId = $session->get('restaurant_id');
        $items = $this->menuItemModel->where('restaurant_id', $restaurantId)->findAll();

        return view('restaurant/menu/index', ['items' => $items]);
    }

    /**
     * Show create menu item form
     */
    public function create()
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return redirect()->to('/login')->with('error', 'Unauthorized');
        }

        return view('restaurant/menu/create');
    }

    /**
     * Store new menu item
     */
    public function store()
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $restaurantId = $session->get('restaurant_id');

        if (! $restaurantId) {
            // probably the user session was not linked to a restaurant yet
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Restaurant information missing. Please logout and login again or contact support.']);
        }

        // validate input
        $rules = [
            'name'  => 'required|min_length[2]|max_length[255]',
            'price' => 'required|decimal',
            'category' => 'permit_empty|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => $this->validator->getErrors()]);
        }

        $data = [
            'restaurant_id' => $restaurantId,
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'price' => $this->request->getPost('price'),
            'discount_price' => $this->request->getPost('discount_price') ?: null,
            'category' => $this->request->getPost('category'),
            'is_available' => $this->request->getPost('is_available') ? 1 : 0,
        ];

        // handle image upload
        $image = $this->request->getFile('image');
        if ($image && $image->isValid() && ! $image->hasMoved()) {
            // make sure upload directory exists
            if (! is_dir(FCPATH . 'uploads/menu')) {
                mkdir(FCPATH . 'uploads/menu', 0755, true);
            }
            $newName = $image->getRandomName();
            // move into public folder so the webserver can serve it
            $image->move(FCPATH . 'uploads/menu', $newName);
            $data['image'] = 'uploads/menu/' . $newName;
        }

        if ($this->menuItemModel->insert($data)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Menu item created']);
        }

        return $this->response->setStatusCode(400)->setJSON(['error' => 'Failed to create menu item']);
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return redirect()->to('/login')->with('error', 'Unauthorized');
        }

        $item = $this->menuItemModel->find($id);
        if (!$item || $item['restaurant_id'] != $session->get('restaurant_id')) {
            return redirect()->to('/menu')->with('error', 'Menu item not found');
        }

        return view('restaurant/menu/edit', ['item' => $item]);
    }

    /**
     * Update menu item
     */
    public function update($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $item = $this->menuItemModel->find($id);
        if (!$item || $item['restaurant_id'] != $session->get('restaurant_id')) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Menu item not found']);
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'price' => $this->request->getPost('price'),
            'discount_price' => $this->request->getPost('discount_price') ?: null,
            'category' => $this->request->getPost('category'),
            'is_available' => $this->request->getPost('is_available') ? 1 : 0,
        ];

        // image upload (optional)
        $image = $this->request->getFile('image');
        if ($image && $image->isValid() && ! $image->hasMoved()) {
            if (! is_dir(FCPATH . 'uploads/menu')) {
                mkdir(FCPATH . 'uploads/menu', 0755, true);
            }
            $newName = $image->getRandomName();
            $image->move(FCPATH . 'uploads/menu', $newName);
            $data['image'] = 'uploads/menu/' . $newName;
        }

        if ($this->menuItemModel->update($id, $data)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Menu item updated']);
        }

        return $this->response->setStatusCode(400)->setJSON(['error' => 'Failed to update menu item']);
    }

    /**
     * Toggle availability
     */
    public function toggleAvailability($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $item = $this->menuItemModel->find($id);
        if (!$item || $item['restaurant_id'] != $session->get('restaurant_id')) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Menu item not found']);
        }

        $newStatus = !$item['is_available'];
        $this->menuItemModel->update($id, ['is_available' => $newStatus]);

        return $this->response->setJSON(['success' => true, 'is_available' => $newStatus]);
    }

    /**
     * Delete menu item
     */
    public function delete($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $item = $this->menuItemModel->find($id);
        if (!$item || $item['restaurant_id'] != $session->get('restaurant_id')) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Menu item not found']);
        }

        if ($this->menuItemModel->delete($id)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Menu item deleted']);
        }

        return $this->response->setStatusCode(400)->setJSON(['error' => 'Failed to delete menu item']);
    }
}
