<?php

namespace App\Controllers;

use App\Models\MenuModel;

class MenuItems extends BaseController
{
    protected $menuItemModel;

    protected $supportsArchive = false;

    public function __construct()
    {
        $this->menuItemModel = new MenuModel();

        // Some installations do not have deleted_at on menu tables.
        $db = db_connect();
        if ($db->tableExists('menus') && $db->fieldExists('deleted_at', 'menus')) {
            $this->supportsArchive = true;
        } elseif ($db->tableExists('menu_items') && $db->fieldExists('deleted_at', 'menu_items')) {
            $this->supportsArchive = true;
        }
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

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_menu_items')) {
            return view('errors/unauthorized', ['message' => 'Permission denied']);
        }

        $restaurantId = $session->get('restaurant_id');
        $q = trim((string) $this->request->getGet('q'));
        $category = trim((string) $this->request->getGet('category'));
        $perPage = (int) ($this->request->getGet('perPage') ?? 9);
        if ($perPage <= 0) {
            $perPage = 9;
        }
        if ($perPage > 60) {
            $perPage = 60;
        }

        $itemsQuery = $this->menuItemModel->where('restaurant_id', $restaurantId);
        if ($this->supportsArchive) {
            $itemsQuery = $itemsQuery->where('deleted_at', null);
        }

        if ($category !== '') {
            $itemsQuery = $itemsQuery->where('category', $category);
        }

        if ($q !== '') {
            $itemsQuery = $itemsQuery
                ->groupStart()
                ->like('name', $q)
                ->orLike('category', $q)
                ->groupEnd();
        }

        $items = $itemsQuery
            ->orderBy('created_at', 'DESC')
            ->paginate($perPage, 'menu');

        $pager = $this->menuItemModel->pager;

        $archivedItems = [];
        if ($this->supportsArchive) {
            $archivedItems = $this->menuItemModel
                ->where('restaurant_id', $restaurantId)
                ->where('deleted_at IS NOT NULL', null, false)
                ->orderBy('deleted_at', 'DESC')
                ->findAll();
        }

        $categoryRows = $this->menuItemModel
            ->select('category')
            ->where('restaurant_id', $restaurantId)
            ->where("TRIM(COALESCE(category, '')) != ''", null, false)
            ->groupBy('category')
            ->orderBy('category', 'ASC')
            ->findAll();

        $categories = [];
        foreach ($categoryRows as $row) {
            $cat = trim((string) ($row['category'] ?? ''));
            if ($cat !== '') {
                $categories[] = $cat;
            }
        }

        foreach ($items as &$item) {
            $item['image'] = $item['image_url'] ?? null;
            $item['is_available'] = (int) ($item['availability'] ?? 1);
        }

        foreach ($archivedItems as &$item) {
            $item['image'] = $item['image_url'] ?? null;
            $item['is_available'] = (int) ($item['availability'] ?? 1);
        }

        return view('restaurant/menu/index', [
            'items' => $items,
            'archivedItems' => $archivedItems,
            'pager' => $pager,
            'q' => $q,
            'category' => $category,
            'perPage' => $perPage,
            'categories' => $categories,
            'supportsArchive' => $this->supportsArchive,
        ]);
    }

    /**
     * Return a single menu item as JSON for modals (view/edit)
     */
    public function show($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_menu_items')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
        }

        $item = $this->menuItemModel->find($id);
        if (!$item || $item['restaurant_id'] != $session->get('restaurant_id')) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Menu item not found']);
        }

        $item['image'] = $item['image_url'] ?? null;
        $item['is_available'] = (int) ($item['availability'] ?? 1);

        return $this->response->setJSON(['success' => true, 'item' => $item]);
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

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_menu_items')) {
            return view('errors/unauthorized', ['message' => 'Permission denied']);
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

            $permissions = new \App\Libraries\PermissionService();
            if (!$permissions->hasPermission('manage_menu_items')) {
                return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
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
                'deleted_at' => null,
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

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_menu_items')) {
            return view('errors/unauthorized', ['message' => 'Permission denied']);
        }

        $item = $this->menuItemModel->withDeleted()->find($id);
        if (!$item || $item['restaurant_id'] != $session->get('restaurant_id')) {
            return redirect()->to('/menu')->with('error', 'Menu item not found');
        }

        if (!empty($item['deleted_at'])) {
            return redirect()->to('/menu')->with('error', 'Archived menu items cannot be edited. Restore the item first.');
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

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_menu_items')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
        }

        $item = $this->menuItemModel->find($id);
        if (!$item || $item['restaurant_id'] != $session->get('restaurant_id')) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Menu item not found']);
        }

        if ($this->supportsArchive && !empty($item['deleted_at'])) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Archived menu items cannot be updated. Restore the item first.']);
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

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_menu_items')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
        }

        $item = $this->menuItemModel->find($id);
        if (!$item || $item['restaurant_id'] != $session->get('restaurant_id')) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Menu item not found']);
        }

        if ($this->supportsArchive && !empty($item['deleted_at'])) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Archived items cannot change availability.']);
        }

        $newStatus = !((int) ($item['availability'] ?? 1));
        $this->menuItemModel->update($id, [
            'availability' => (int) $newStatus,
        ]);

        return $this->response->setJSON(['success' => true, 'is_available' => $newStatus]);
    }

    /**
    * Archive menu item
     */
    public function delete($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_menu_items')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
        }

        $item = $this->menuItemModel->find($id);
        if (!$item || $item['restaurant_id'] != $session->get('restaurant_id')) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Menu item not found']);
        }

        if ($this->supportsArchive) {
            if (!empty($item['deleted_at'])) {
                return $this->response->setJSON(['success' => true, 'message' => 'Menu item is already archived']);
            }

            if ($this->menuItemModel->update($id, ['deleted_at' => date('Y-m-d H:i:s')])) {
                return $this->response->setJSON(['success' => true, 'message' => 'Menu item archived']);
            }

            return $this->response->setStatusCode(400)->setJSON(['error' => 'Failed to archive menu item']);
        }

        if ($this->menuItemModel->delete($id)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Menu item deleted']);
        }

        return $this->response->setStatusCode(400)->setJSON(['error' => 'Failed to delete menu item']);
    }

    /**
     * Permanently delete a menu item (only available when archive is enabled)
     */
    public function destroy($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_menu_items')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
        }

        if (! $this->supportsArchive) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Permanent delete is not available on this database schema']);
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

    /**
     * Restore archived menu item
     */
    public function restore($id)
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $permissions = new \App\Libraries\PermissionService();
        if (!$permissions->hasPermission('manage_menu_items')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied']);
        }

        if (! $this->supportsArchive) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Archive/restore is not available on this database schema']);
        }

        $item = $this->menuItemModel->find($id);
        if (!$item || $item['restaurant_id'] != $session->get('restaurant_id')) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Menu item not found']);
        }

        if (empty($item['deleted_at'])) {
            return $this->response->setJSON(['success' => true, 'message' => 'Menu item is already active']);
        }

        if ($this->menuItemModel->update($id, ['deleted_at' => null])) {
            return $this->response->setJSON(['success' => true, 'message' => 'Menu item restored']);
        }

        return $this->response->setStatusCode(400)->setJSON(['error' => 'Failed to restore menu item']);
    }
}
