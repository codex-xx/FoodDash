<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderItemModel extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'order_id',
        'menu_id',
        'item_name',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $returnType = 'array';
}
