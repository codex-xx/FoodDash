<?php

namespace App\Controllers\Api;

use App\Libraries\BusinessTransactionService;
use App\Models\ProductModel;
use App\Models\TransactionDetailModel;
use App\Models\TransactionModel;
use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class BusinessTransactionController extends ResourceController
{
    protected $format = 'json';

    protected function payload(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json)) {
            return $json;
        }

        $post = $this->request->getPost();
        return is_array($post) ? $post : [];
    }

    protected function actorUserId(): int
    {
        return (int) (session()->get('user_id') ?? 0);
    }

    public function users()
    {
        $rows = (new UserModel())
            ->select('id, email, role, is_active, created_at, updated_at')
            ->orderBy('id', 'DESC')
            ->findAll(200);

        return $this->respond([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function createUser()
    {
        $payload = $this->payload();

        $rules = [
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'role' => 'required|in_list[admin,restaurant]',
        ];

        if (! $this->validateData($payload, $rules)) {
            return $this->respond([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $this->validator->getErrors(),
            ], 400);
        }

        $model = new UserModel();
        $model->insert([
            'email' => trim((string) $payload['email']),
            'password' => (string) $payload['password'],
            'role' => (string) $payload['role'],
            'is_active' => isset($payload['is_active']) ? (int) ((bool) $payload['is_active']) : 1,
            'token_version' => 1,
        ]);

        $insertId = (int) $model->getInsertID();

        (new BusinessTransactionService())->logActivity(
            $this->actorUserId(),
            'user_create',
            'users',
            $insertId,
            'success',
            'User created via business CRUD.',
            ['role' => $payload['role']],
            (string) $this->request->getIPAddress()
        );

        return $this->respondCreated([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => ['id' => $insertId],
        ]);
    }

    public function showUser($id = null)
    {
        $userId = (int) $id;
        $row = (new UserModel())
            ->select('id, email, role, is_active, created_at, updated_at')
            ->find($userId);

        if (! $row) {
            return $this->respond(['success' => false, 'message' => 'User not found.'], 404);
        }

        return $this->respond(['success' => true, 'data' => $row]);
    }

    public function updateUser($id = null)
    {
        $userId = (int) $id;
        $payload = $this->payload();

        $model = new UserModel();
        $existing = $model->find($userId);
        if (! $existing) {
            return $this->respond(['success' => false, 'message' => 'User not found.'], 404);
        }

        $rules = [
            'email' => 'permit_empty|valid_email',
            'password' => 'permit_empty|min_length[8]',
            'role' => 'permit_empty|in_list[admin,restaurant]',
            'is_active' => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validateData($payload, $rules)) {
            return $this->respond([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $this->validator->getErrors(),
            ], 400);
        }

        $updateData = [];
        if (isset($payload['email'])) {
            $updateData['email'] = trim((string) $payload['email']);
        }
        if (isset($payload['password']) && trim((string) $payload['password']) !== '') {
            $updateData['password'] = (string) $payload['password'];
        }
        if (isset($payload['role'])) {
            $updateData['role'] = (string) $payload['role'];
        }
        if (isset($payload['is_active'])) {
            $updateData['is_active'] = (int) ((bool) $payload['is_active']);
        }

        if ($updateData === []) {
            return $this->respond(['success' => false, 'message' => 'No valid fields to update.'], 400);
        }

        $model->update($userId, $updateData);

        (new BusinessTransactionService())->logActivity(
            $this->actorUserId(),
            'user_update',
            'users',
            $userId,
            'success',
            'User updated via business CRUD.',
            array_keys($updateData),
            (string) $this->request->getIPAddress()
        );

        return $this->respond([
            'success' => true,
            'message' => 'User updated successfully.',
        ]);
    }

    public function deleteUser($id = null)
    {
        $userId = (int) $id;
        $model = new UserModel();
        $existing = $model->find($userId);
        if (! $existing) {
            return $this->respond(['success' => false, 'message' => 'User not found.'], 404);
        }

        $model->update($userId, [
            'is_active' => 0,
            'token_version' => (int) ($existing['token_version'] ?? 1) + 1,
        ]);

        (new BusinessTransactionService())->logActivity(
            $this->actorUserId(),
            'user_deactivate',
            'users',
            $userId,
            'success',
            'User deactivated instead of hard delete.',
            null,
            (string) $this->request->getIPAddress()
        );

        return $this->respond(['success' => true, 'message' => 'User deactivated successfully.']);
    }

    public function products()
    {
        $rows = (new ProductModel())
            ->orderBy('id', 'DESC')
            ->findAll(300);

        return $this->respond(['success' => true, 'data' => $rows]);
    }

    public function createProduct()
    {
        $payload = $this->payload();

        $rules = [
            'sku' => 'required|max_length[60]|is_unique[products.sku]',
            'name' => 'required|max_length[140]',
            'description' => 'permit_empty|max_length[2000]',
            'unit_price' => 'required|decimal',
            'stock_quantity' => 'required|integer|greater_than_equal_to[0]',
            'is_active' => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validateData($payload, $rules)) {
            return $this->respond([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $this->validator->getErrors(),
            ], 400);
        }

        $model = new ProductModel();
        $model->insert([
            'sku' => trim((string) $payload['sku']),
            'name' => trim((string) $payload['name']),
            'description' => isset($payload['description']) ? (string) $payload['description'] : null,
            'unit_price' => (float) $payload['unit_price'],
            'stock_quantity' => (int) $payload['stock_quantity'],
            'is_active' => isset($payload['is_active']) ? (int) ((bool) $payload['is_active']) : 1,
        ]);

        $productId = (int) $model->getInsertID();

        (new BusinessTransactionService())->logActivity(
            $this->actorUserId(),
            'product_create',
            'products',
            $productId,
            'success',
            'Product created.',
            ['sku' => $payload['sku']],
            (string) $this->request->getIPAddress()
        );

        return $this->respondCreated([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => ['id' => $productId],
        ]);
    }

    public function showProduct($id = null)
    {
        $row = (new ProductModel())->find((int) $id);
        if (! $row) {
            return $this->respond(['success' => false, 'message' => 'Product not found.'], 404);
        }

        return $this->respond(['success' => true, 'data' => $row]);
    }

    public function updateProduct($id = null)
    {
        $productId = (int) $id;
        $payload = $this->payload();

        $model = new ProductModel();
        if (! $model->find($productId)) {
            return $this->respond(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $rules = [
            'sku' => 'permit_empty|max_length[60]',
            'name' => 'permit_empty|max_length[140]',
            'description' => 'permit_empty|max_length[2000]',
            'unit_price' => 'permit_empty|decimal',
            'stock_quantity' => 'permit_empty|integer|greater_than_equal_to[0]',
            'is_active' => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validateData($payload, $rules)) {
            return $this->respond([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $this->validator->getErrors(),
            ], 400);
        }

        $updateData = [];
        foreach (['sku', 'name', 'description', 'unit_price', 'stock_quantity', 'is_active'] as $field) {
            if (array_key_exists($field, $payload)) {
                $updateData[$field] = in_array($field, ['unit_price'], true)
                    ? (float) $payload[$field]
                    : (in_array($field, ['stock_quantity', 'is_active'], true) ? (int) $payload[$field] : (string) $payload[$field]);
            }
        }

        if ($updateData === []) {
            return $this->respond(['success' => false, 'message' => 'No valid fields to update.'], 400);
        }

        $model->update($productId, $updateData);

        (new BusinessTransactionService())->logActivity(
            $this->actorUserId(),
            'product_update',
            'products',
            $productId,
            'success',
            'Product updated.',
            array_keys($updateData),
            (string) $this->request->getIPAddress()
        );

        return $this->respond(['success' => true, 'message' => 'Product updated successfully.']);
    }

    public function deleteProduct($id = null)
    {
        $productId = (int) $id;
        $model = new ProductModel();
        if (! $model->find($productId)) {
            return $this->respond(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $model->delete($productId);

        (new BusinessTransactionService())->logActivity(
            $this->actorUserId(),
            'product_delete',
            'products',
            $productId,
            'success',
            'Product soft deleted.',
            null,
            (string) $this->request->getIPAddress()
        );

        return $this->respond(['success' => true, 'message' => 'Product deleted successfully.']);
    }

    public function transactions()
    {
        $rows = (new TransactionModel())
            ->select('id, reference_no, user_id, status, subtotal, tax_amount, total_amount, created_at')
            ->orderBy('id', 'DESC')
            ->findAll(200);

        return $this->respond(['success' => true, 'data' => $rows]);
    }

    public function showTransaction($id = null)
    {
        $transactionId = (int) $id;
        $transaction = (new TransactionModel())->find($transactionId);
        if (! $transaction) {
            return $this->respond(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        $details = (new TransactionDetailModel())
            ->where('transaction_id', $transactionId)
            ->findAll();

        return $this->respond([
            'success' => true,
            'data' => [
                'transaction' => $transaction,
                'details' => $details,
            ],
        ]);
    }

    public function createTransaction()
    {
        $service = new BusinessTransactionService();
        $result = $service->createTransaction(
            $this->payload(),
            $this->actorUserId(),
            (string) $this->request->getIPAddress()
        );

        return $this->respond($result, ($result['success'] ?? false) ? 201 : 400);
    }

    public function updateTransaction($id = null)
    {
        $service = new BusinessTransactionService();
        $result = $service->updateTransaction(
            (int) $id,
            $this->payload(),
            $this->actorUserId(),
            (string) $this->request->getIPAddress()
        );

        return $this->respond($result, ($result['success'] ?? false) ? 200 : 400);
    }

    public function deleteTransaction($id = null)
    {
        $service = new BusinessTransactionService();
        $result = $service->deleteTransaction(
            (int) $id,
            $this->actorUserId(),
            (string) $this->request->getIPAddress()
        );

        return $this->respond($result, ($result['success'] ?? false) ? 200 : 400);
    }
}
