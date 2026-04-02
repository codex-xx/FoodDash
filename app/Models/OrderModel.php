<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'order_number',
        'customer_id',
        'customer_name',
        'restaurant_id',
        'driver_id',
        'delivery_type_id',
        'order_size_category',
        'status',
        'total_amount',
        'delivery_address',
        'items',
        'notes',
        'estimated_preparation_time',
    ];

    protected $returnType = 'array';
}
