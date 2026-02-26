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
        'status',
        'total_amount',
        'delivery_address',
        'items',
        'notes',
    ];

    protected $returnType = 'array';
}
