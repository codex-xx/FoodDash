<?php

namespace App\Libraries;

use App\Models\ActivityLogModel;
use App\Models\ProductModel;
use App\Models\TransactionDetailModel;
use App\Models\TransactionModel;

class BusinessTransactionService
{
    protected SensitiveDataService $sensitive;

    public function __construct()
    {
        $this->sensitive = new SensitiveDataService();
    }

    public function createTransaction(array $payload, int $actorUserId, string $ipAddress): array
    {
        $userId = (int) ($payload['user_id'] ?? 0);
        $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [];
        $notes = isset($payload['notes']) ? (string) $payload['notes'] : null;
        $taxAmount = isset($payload['tax_amount']) ? (float) $payload['tax_amount'] : 0.0;

        if ($userId <= 0 || $items === []) {
            return [
                'success' => false,
                'message' => 'user_id and at least one item are required.',
            ];
        }

        $db = db_connect();

        try {
            $db->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $db->transBegin();

            $preparedItems = $this->prepareAndDeductItems($items, $db);
            $subtotal = (float) $preparedItems['subtotal'];
            $lineItems = $preparedItems['line_items'];
            $total = $subtotal + $taxAmount;
            $reference = $this->generateReferenceNo($db);

            $transactionModel = new TransactionModel();
            $transactionModel->insert([
                'reference_no' => $reference,
                'user_id' => $userId,
                'status' => 'completed',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
                'notes' => $notes,
                'created_by' => $actorUserId,
                'updated_by' => $actorUserId,
            ]);

            $transactionId = (int) $transactionModel->getInsertID();
            if ($transactionId <= 0) {
                throw new \RuntimeException('Failed to create transaction record.');
            }

            $detailModel = new TransactionDetailModel();
            foreach ($lineItems as $item) {
                $detailModel->insert([
                    'transaction_id' => $transactionId,
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (float) $item['unit_price'],
                    'line_total' => (float) $item['line_total'],
                ]);
            }

            $this->logActivity(
                $actorUserId,
                'transaction_create',
                'transactions',
                $transactionId,
                'success',
                'Transaction created and inventory deducted.',
                ['reference_no' => $reference, 'item_count' => count($lineItems)],
                $ipAddress
            );

            if (! $db->transStatus()) {
                throw new \RuntimeException('Transaction failed before commit.');
            }

            $db->transCommit();

            return [
                'success' => true,
                'message' => 'Transaction created successfully.',
                'data' => [
                    'transaction_id' => $transactionId,
                    'reference_no' => $reference,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $total,
                ],
            ];
        } catch (\Throwable $e) {
            if ($db->transStatus() !== false) {
                $db->transRollback();
            }

            $this->logActivity(
                $actorUserId,
                'transaction_create',
                'transactions',
                null,
                'failed',
                'Transaction create failed and rolled back.',
                ['error' => $e->getMessage()],
                $ipAddress
            );

            return [
                'success' => false,
                'message' => 'Unable to create transaction. Operation was rolled back.',
            ];
        }
    }

    public function updateTransaction(int $transactionId, array $payload, int $actorUserId, string $ipAddress): array
    {
        if ($transactionId <= 0) {
            return ['success' => false, 'message' => 'Invalid transaction ID.'];
        }

        $items = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [];
        $notes = isset($payload['notes']) ? (string) $payload['notes'] : null;
        $taxAmount = isset($payload['tax_amount']) ? (float) $payload['tax_amount'] : 0.0;

        if ($items === []) {
            return ['success' => false, 'message' => 'At least one line item is required.'];
        }

        $db = db_connect();

        try {
            $db->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $db->transBegin();

            $transactionModel = new TransactionModel();
            $transaction = $transactionModel->find($transactionId);
            if (! $transaction) {
                throw new \RuntimeException('Transaction not found.');
            }

            $this->restoreInventoryFromExistingDetails($transactionId, $db);

            $preparedItems = $this->prepareAndDeductItems($items, $db);
            $subtotal = (float) $preparedItems['subtotal'];
            $lineItems = $preparedItems['line_items'];
            $total = $subtotal + $taxAmount;

            $detailModel = new TransactionDetailModel();
            $detailModel->where('transaction_id', $transactionId)->delete();

            foreach ($lineItems as $item) {
                $detailModel->insert([
                    'transaction_id' => $transactionId,
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (float) $item['unit_price'],
                    'line_total' => (float) $item['line_total'],
                ]);
            }

            $transactionModel->update($transactionId, [
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
                'notes' => $notes,
                'updated_by' => $actorUserId,
            ]);

            $this->logActivity(
                $actorUserId,
                'transaction_update',
                'transactions',
                $transactionId,
                'success',
                'Transaction updated and inventory reconciled.',
                ['item_count' => count($lineItems)],
                $ipAddress
            );

            if (! $db->transStatus()) {
                throw new \RuntimeException('Transaction update failed before commit.');
            }

            $db->transCommit();

            return [
                'success' => true,
                'message' => 'Transaction updated successfully.',
                'data' => [
                    'transaction_id' => $transactionId,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $total,
                ],
            ];
        } catch (\Throwable $e) {
            if ($db->transStatus() !== false) {
                $db->transRollback();
            }

            $this->logActivity(
                $actorUserId,
                'transaction_update',
                'transactions',
                $transactionId,
                'failed',
                'Transaction update failed and rolled back.',
                ['error' => $e->getMessage()],
                $ipAddress
            );

            return [
                'success' => false,
                'message' => 'Unable to update transaction. Operation was rolled back.',
            ];
        }
    }

    public function deleteTransaction(int $transactionId, int $actorUserId, string $ipAddress): array
    {
        if ($transactionId <= 0) {
            return ['success' => false, 'message' => 'Invalid transaction ID.'];
        }

        $db = db_connect();

        try {
            $db->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $db->transBegin();

            $transactionModel = new TransactionModel();
            $transaction = $transactionModel->find($transactionId);
            if (! $transaction) {
                throw new \RuntimeException('Transaction not found.');
            }

            $this->restoreInventoryFromExistingDetails($transactionId, $db);

            $transactionModel->update($transactionId, [
                'status' => 'cancelled',
                'updated_by' => $actorUserId,
            ]);
            $transactionModel->delete($transactionId);

            $this->logActivity(
                $actorUserId,
                'transaction_delete',
                'transactions',
                $transactionId,
                'success',
                'Transaction cancelled and soft deleted.',
                null,
                $ipAddress
            );

            if (! $db->transStatus()) {
                throw new \RuntimeException('Transaction delete failed before commit.');
            }

            $db->transCommit();

            return [
                'success' => true,
                'message' => 'Transaction deleted successfully.',
            ];
        } catch (\Throwable $e) {
            if ($db->transStatus() !== false) {
                $db->transRollback();
            }

            $this->logActivity(
                $actorUserId,
                'transaction_delete',
                'transactions',
                $transactionId,
                'failed',
                'Transaction delete failed and rolled back.',
                ['error' => $e->getMessage()],
                $ipAddress
            );

            return [
                'success' => false,
                'message' => 'Unable to delete transaction. Operation was rolled back.',
            ];
        }
    }

    protected function prepareAndDeductItems(array $items, $db): array
    {
        $subtotal = 0.0;
        $lineItems = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                throw new \RuntimeException('Invalid product line item payload.');
            }

            $product = $db->query(
                'SELECT id, name, unit_price, stock_quantity, is_active, deleted_at FROM products WHERE id = ? FOR UPDATE',
                [$productId]
            )->getRowArray();

            if (! $product || ! is_null($product['deleted_at']) || (int) $product['is_active'] !== 1) {
                throw new \RuntimeException('Product not available: ' . $productId);
            }

            $available = (int) ($product['stock_quantity'] ?? 0);
            if ($available < $quantity) {
                throw new \RuntimeException('Insufficient stock for product: ' . $productId);
            }

            $unitPrice = isset($item['unit_price'])
                ? (float) $item['unit_price']
                : (float) ($product['unit_price'] ?? 0);

            $lineTotal = $unitPrice * $quantity;

            $db->table('products')
                ->where('id', $productId)
                ->set('stock_quantity', 'stock_quantity - ' . $quantity, false)
                ->update();

            $lineItems[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];

            $subtotal += $lineTotal;
        }

        return [
            'subtotal' => $subtotal,
            'line_items' => $lineItems,
        ];
    }

    protected function restoreInventoryFromExistingDetails(int $transactionId, $db): void
    {
        $details = $db->table('transaction_details')
            ->select('product_id, quantity')
            ->where('transaction_id', $transactionId)
            ->get()
            ->getResultArray();

        foreach ($details as $detail) {
            $productId = (int) ($detail['product_id'] ?? 0);
            $quantity = (int) ($detail['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $db->query('SELECT id FROM products WHERE id = ? FOR UPDATE', [$productId]);
            $db->table('products')
                ->where('id', $productId)
                ->set('stock_quantity', 'stock_quantity + ' . $quantity, false)
                ->update();
        }
    }

    protected function generateReferenceNo($db): string
    {
        do {
            $reference = 'TRX-' . date('YmdHis') . '-' . random_int(1000, 9999);
            $exists = $db->table('transactions')
                ->select('id')
                ->where('reference_no', $reference)
                ->get()
                ->getRowArray();
        } while ($exists !== null);

        return $reference;
    }

    public function logActivity(
        ?int $actorUserId,
        string $action,
        string $entityType,
        ?int $entityId,
        string $status,
        ?string $message,
        ?array $context,
        string $ipAddress
    ): void {
        if (! $this->tableExists('activity_logs')) {
            return;
        }

        $model = new ActivityLogModel();
        $json = is_array($context) ? json_encode($context, JSON_UNESCAPED_SLASHES) : null;

        $model->insert([
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'status' => $status === 'failed' ? 'failed' : 'success',
            'message' => $message,
            'context_json' => $json === false ? null : $json,
            'ip_hash' => $this->sensitive->hashForLookup($ipAddress),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function tableExists(string $table): bool
    {
        try {
            return db_connect()->tableExists($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
