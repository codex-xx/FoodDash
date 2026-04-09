<?php

namespace App\Controllers\Api;

use App\Models\MenuModel;
use App\Libraries\PermissionService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class MenusController extends ResourceController
{
    protected $format = 'json';
    protected MenuModel $menuItemModel;
    protected PermissionService $permissions;

    public function __construct()
    {
        $this->menuItemModel = new MenuModel();
        $this->permissions = new PermissionService();
    }

    /**
     * GET /api/menus?restaurant_id={id}
     */
    public function index(): ResponseInterface
    {
        $restaurantId = $this->request->getGet('restaurant_id');

        if (empty($restaurantId) || ! ctype_digit((string) $restaurantId)) {
            return $this->respond([
                'success' => false,
                'message' => 'restaurant_id is required and must be numeric',
            ], 400);
        }

        $query = $this->menuItemModel
            ->where('restaurant_id', (int) $restaurantId)
            ->orderBy('category', 'ASC')
            ->orderBy('name', 'ASC');

        $menus = $query->findAll();

        $data = array_map(function (array $menu): array {
            $imagePath = $menu['image_url'] ?? null;
            $availability = (int) ($menu['availability'] ?? 1);

            return [
                'id' => (int) $menu['id'],
                'restaurant_id' => (int) $menu['restaurant_id'],
                'name' => $menu['name'],
                'description' => $menu['description'],
                'price' => (float) $menu['price'],
                'image_url' => $this->toAbsoluteImageUrl($imagePath),
                'category' => $menu['category'],
                'availability' => $availability,
                'is_available' => $availability,
                'can_order' => $availability === 1,
                'ui_disabled' => $availability !== 1,
                'availability_message' => $availability === 1 ? null : 'Not available for a moment',
                'created_at' => $menu['created_at'],
                'updated_at' => $menu['updated_at'],
            ];
        }, $menus);

        return $this->respond([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * POST /api/menus
     */
    public function create(): ResponseInterface
    {
        $authError = $this->authorizeMenuWrite();
        if ($authError instanceof ResponseInterface) {
            return $authError;
        }

        $payload = $this->request->getPost();

        $rules = [
            'restaurant_id' => 'required|integer',
            'name' => 'required|min_length[2]|max_length[255]',
            'description' => 'permit_empty',
            'price' => 'required|decimal',
            'category' => 'permit_empty|max_length[100]',
            'availability' => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return $this->respond([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $restaurantId = (int) $payload['restaurant_id'];

        if (! $this->canWriteRestaurant($restaurantId)) {
            return $this->respond([
                'success' => false,
                'message' => 'You are not allowed to modify menus for this restaurant',
            ], 403);
        }

        $imagePath = $this->handleImageUpload();
        if (isset($imagePath['error'])) {
            return $this->respond([
                'success' => false,
                'message' => $imagePath['error'],
            ], 400);
        }

        $availability = isset($payload['availability']) ? (int) $payload['availability'] : 1;

        $data = [
            'restaurant_id' => $restaurantId,
            'name' => trim((string) $payload['name']),
            'description' => $payload['description'] ?? null,
            'price' => $payload['price'],
            'category' => $payload['category'] ?? null,
            'image_url' => $imagePath['path'] ?? ($payload['image_url'] ?? null),
            'availability' => $availability,
        ];

        $newId = $this->menuItemModel->insert($data, true);

        if (! $newId) {
            return $this->respond([
                'success' => false,
                'message' => 'Failed to create menu item',
                'errors' => $this->menuItemModel->errors(),
            ], 400);
        }

        $created = $this->menuItemModel->find($newId);

        return $this->respondCreated([
            'success' => true,
            'message' => 'Menu item created',
            'data' => $this->serializeMenu($created),
        ]);
    }

    /**
     * PUT /api/menus/{id}
     */
    public function update($id = null): ResponseInterface
    {
        $authError = $this->authorizeMenuWrite();
        if ($authError instanceof ResponseInterface) {
            return $authError;
        }

        $menu = $this->menuItemModel->find($id);
        if (! $menu) {
            return $this->respond([
                'success' => false,
                'message' => 'Menu item not found',
            ], 404);
        }

        if (! $this->canWriteRestaurant((int) $menu['restaurant_id'])) {
            return $this->respond([
                'success' => false,
                'message' => 'You are not allowed to modify this menu item',
            ], 403);
        }

        $input = $this->request->getRawInput();
        if (empty($input)) {
            $input = $this->request->getPost();
        }

        $rules = [
            'name' => 'permit_empty|min_length[2]|max_length[255]',
            'description' => 'permit_empty',
            'price' => 'permit_empty|decimal',
            'category' => 'permit_empty|max_length[100]',
            'availability' => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validateData($input, $rules)) {
            return $this->respond([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors(),
            ], 422);
        }

        $imagePath = $this->handleImageUpload();
        if (isset($imagePath['error'])) {
            return $this->respond([
                'success' => false,
                'message' => $imagePath['error'],
            ], 400);
        }

        $updateData = [];
        foreach (['name', 'description', 'price', 'category'] as $field) {
            if (array_key_exists($field, $input)) {
                $updateData[$field] = $input[$field];
            }
        }

        if (array_key_exists('availability', $input)) {
            $availability = (int) $input['availability'];
            $updateData['availability'] = $availability;
        }

        if (isset($imagePath['path'])) {
            $updateData['image_url'] = $imagePath['path'];
        } elseif (array_key_exists('image_url', $input)) {
            $updateData['image_url'] = $input['image_url'];
        }

        if (empty($updateData)) {
            return $this->respond([
                'success' => false,
                'message' => 'No updatable fields found',
            ], 400);
        }

        if (! $this->menuItemModel->update((int) $id, $updateData)) {
            return $this->respond([
                'success' => false,
                'message' => 'Failed to update menu item',
                'errors' => $this->menuItemModel->errors(),
            ], 400);
        }

        $updated = $this->menuItemModel->find($id);

        return $this->respond([
            'success' => true,
            'message' => 'Menu item updated',
            'data' => $this->serializeMenu($updated),
        ]);
    }

    /**
     * DELETE /api/menus/{id}
     */
    public function delete($id = null): ResponseInterface
    {
        $authError = $this->authorizeMenuWrite();
        if ($authError instanceof ResponseInterface) {
            return $authError;
        }

        $menu = $this->menuItemModel->find($id);
        if (! $menu) {
            return $this->respond([
                'success' => false,
                'message' => 'Menu item not found',
            ], 404);
        }

        if (! $this->canWriteRestaurant((int) $menu['restaurant_id'])) {
            return $this->respond([
                'success' => false,
                'message' => 'You are not allowed to delete this menu item',
            ], 403);
        }

        if (! $this->menuItemModel->delete((int) $id)) {
            return $this->respond([
                'success' => false,
                'message' => 'Failed to delete menu item',
            ], 400);
        }

        return $this->respondDeleted([
            'success' => true,
            'message' => 'Menu item deleted',
        ]);
    }

    private function serializeMenu(array $menu): array
    {
        $imagePath = $menu['image_url'] ?? null;
        $availability = (int) ($menu['availability'] ?? 1);

        return [
            'id' => (int) $menu['id'],
            'restaurant_id' => (int) $menu['restaurant_id'],
            'name' => $menu['name'],
            'description' => $menu['description'],
            'price' => (float) $menu['price'],
            'image_url' => $this->toAbsoluteImageUrl($imagePath),
            'category' => $menu['category'],
            'availability' => $availability,
            'is_available' => $availability,
            'can_order' => $availability === 1,
            'ui_disabled' => $availability !== 1,
            'availability_message' => $availability === 1 ? null : 'Not available for a moment',
            'created_at' => $menu['created_at'],
            'updated_at' => $menu['updated_at'],
        ];
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

    private function handleImageUpload(): array
    {
        $image = $this->request->getFile('image');

        if (! $image || ! $image->isValid() || $image->hasMoved()) {
            return [];
        }

        $mimeType = $image->getMimeType();
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (! in_array($mimeType, $allowed, true)) {
            return ['error' => 'Invalid image type. Allowed: jpg, png, webp, gif'];
        }

        $uploadPath = FCPATH . 'uploads/menu';
        if (! is_dir($uploadPath) && ! mkdir($uploadPath, 0755, true) && ! is_dir($uploadPath)) {
            return ['error' => 'Failed to create upload directory'];
        }

        $newName = $image->getRandomName();
        $image->move($uploadPath, $newName);

        return ['path' => 'uploads/menu/' . $newName];
    }

    private function authorizeMenuWrite(): ?ResponseInterface
    {
        $session = session();
        $isLoggedIn = (bool) $session->get('isLoggedIn');
        $role = (string) $session->get('role');

        if (! $isLoggedIn || ! $this->permissions->allows($role, 'menus', 'write')) {
            return $this->respond([
                'success' => false,
                'message' => 'Only restaurant/admin can modify menu',
            ], 403);
        }

        return null;
    }

    private function canWriteRestaurant(int $restaurantId): bool
    {
        $session = session();
        $role = (string) $session->get('role');

        if ($role === 'admin') {
            return true;
        }

        $sessionRestaurantId = (int) $session->get('restaurant_id');

        return $role === 'restaurant' && $sessionRestaurantId === $restaurantId;
    }
}
