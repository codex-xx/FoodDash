<?php

namespace App\Controllers;

use App\Models\MenuModel;

class MenuItems extends BaseController
{
    protected $menuItemModel;

    public function __construct()
    {
        $this->menuItemModel = new MenuModel();
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

        foreach ($items as &$item) {
            $item['image'] = $item['image_url'] ?? null;
            $item['is_available'] = (int) ($item['availability'] ?? 1);
        }

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
        try {
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
                'category' => $this->request->getPost('category'),
                'availability' => $this->request->getPost('is_available') ? 1 : 0,
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
                $data['image_url'] = 'uploads/menu/' . $newName;
            }

            if ($this->menuItemModel->insert($data)) {
                return $this->response->setJSON(['success' => true, 'message' => 'Menu item created']);
            }

            $modelErrors = $this->menuItemModel->errors();
            if (! empty($modelErrors)) {
                return $this->response->setStatusCode(400)->setJSON(['error' => $modelErrors]);
            }

            $dbError = db_connect()->error();
            if (! empty($dbError['code'])) {
                return $this->response->setStatusCode(400)->setJSON([
                    'error' => 'Failed to create menu item: ' . $dbError['message'],
                ]);
            }

            return $this->response->setStatusCode(400)->setJSON(['error' => 'Failed to create menu item']);
        } catch (\Throwable $e) {
            log_message('error', 'MenuItems::store failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'Server error while creating menu item. ' . $e->getMessage(),
            ]);
        }
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

        $item['image'] = $item['image_url'] ?? null;
        $item['is_available'] = (int) ($item['availability'] ?? 1);

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
            'category' => $this->request->getPost('category'),
            'availability' => $this->request->getPost('is_available') ? 1 : 0,
        ];

        // image upload (optional)
        $image = $this->request->getFile('image');
        if ($image && $image->isValid() && ! $image->hasMoved()) {
            if (! is_dir(FCPATH . 'uploads/menu')) {
                mkdir(FCPATH . 'uploads/menu', 0755, true);
            }
            $newName = $image->getRandomName();
            $image->move(FCPATH . 'uploads/menu', $newName);
            $data['image_url'] = 'uploads/menu/' . $newName;
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

        $newStatus = !((int) ($item['availability'] ?? 1));
        $this->menuItemModel->update($id, [
            'availability' => (int) $newStatus,
        ]);

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
