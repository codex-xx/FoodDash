<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentTransactionModel extends Model
{
	protected $table = 'payment_transactions';
	protected $primaryKey = 'id';
	protected $returnType = 'array';

	protected $useTimestamps = true;
	protected $createdField = 'created_at';
	protected $updatedField = 'updated_at';

	protected $allowedFields = [
		'order_id',
		'customer_id',
		'restaurant_id',
		'provider',
		'reference',
		'amount',
		'currency',
		'status',
		'payment_payload_encrypted',
		'payment_payload_hash',
		'paid_at',
	];
}
