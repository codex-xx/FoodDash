# CRUD-Based Business Transaction System (FoodDash)

## Implemented Components

### Database
- Migration: `app/Database/Migrations/20260416113000_create_business_transaction_tables.php`
- SQL schema file: `database/business_transaction_schema.sql`

### Models
- `app/Models/ProductModel.php`
- `app/Models/TransactionModel.php`
- `app/Models/TransactionDetailModel.php`
- `app/Models/ActivityLogModel.php`

### Services and Controllers
- `app/Libraries/BusinessTransactionService.php`
- `app/Controllers/Api/BusinessTransactionController.php`

### Routes and Access Control
- Routes in `app/Config/Routes.php` under `api/admin/business/*`
- Protected by admin filter in `app/Config/Filters.php` (`apiadmin`)

## API Endpoints (Admin Only)

### Users CRUD
- `GET /api/admin/business/users`
- `POST /api/admin/business/users`
- `GET /api/admin/business/users/{id}`
- `PUT|POST /api/admin/business/users/{id}`
- `DELETE /api/admin/business/users/{id}`

### Products CRUD
- `GET /api/admin/business/products`
- `POST /api/admin/business/products`
- `GET /api/admin/business/products/{id}`
- `PUT|POST /api/admin/business/products/{id}`
- `DELETE /api/admin/business/products/{id}` (soft delete)

### Transactions CRUD
- `GET /api/admin/business/transactions`
- `POST /api/admin/business/transactions`
- `GET /api/admin/business/transactions/{id}`
- `PUT|POST /api/admin/business/transactions/{id}`
- `DELETE /api/admin/business/transactions/{id}` (cancel + soft delete)

## Sample Commit / Rollback Logic

From `BusinessTransactionService::createTransaction()`:

```php
$db->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
$db->transBegin();

try {
    $preparedItems = $this->prepareAndDeductItems($items, $db); // includes row locking + stock checks

    $transactionModel->insert([...]);
    $transactionId = (int) $transactionModel->getInsertID();

    foreach ($lineItems as $item) {
        $detailModel->insert([...]);
    }

    if (! $db->transStatus()) {
        throw new \RuntimeException('Transaction failed before commit.');
    }

    $db->transCommit();
} catch (\Throwable $e) {
    $db->transRollback();
}
```

## ACID Enforcement

### Atomicity
- Multi-step transaction flow (header insert + detail inserts + inventory update) is wrapped in one DB transaction.
- Any failure triggers rollback, preventing partial writes.

### Consistency
- Validation enforces required fields and value ranges.
- Foreign keys keep details linked to valid transactions/products.
- Stock is validated before deduction to prevent invalid states.

### Isolation
- Uses `READ COMMITTED` isolation level per transaction.
- Uses row-level lock with `SELECT ... FOR UPDATE` when checking/updating stock.

### Durability
- Uses InnoDB transactional storage and explicit `COMMIT`.
- After commit, rows are durable across server restarts.

## Security and Integrity Measures
- Uses prepared statements/bindings via Query Builder and bound raw SQL.
- Sanitizes and validates payloads using CodeIgniter validation rules.
- Restricts endpoints to admin users via `apiadmin` filter.
- Logs critical business operations (success/failure) into `activity_logs`.
- Uses soft delete for products and transactions to preserve recoverability.

## Error Handling and Recovery
- Uses try/catch around transactional operations.
- Returns safe error messages without exposing stack traces.
- Logs failed operations for audit and troubleshooting.
