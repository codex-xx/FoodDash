<?php

namespace App\Models;

use CodeIgniter\Model;

class DeliveryRecordModel extends Model
{
	protected $table = 'delivery_records';
	protected $primaryKey = 'id';
	protected $returnType = 'array';

	protected $useTimestamps = true;
	protected $createdField = 'created_at';
	protected $updatedField = 'updated_at';

	protected $allowedFields = [
		'order_id',
		'driver_id',
		'status',
		'pickup_time',
		'delivered_time',
		'distance_km',
		'proof_image',
		'notes',
	];
}
