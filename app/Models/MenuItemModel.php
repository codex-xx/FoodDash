<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuItemModel extends Model
{
    protected $table = 'menu_items';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'restaurant_id',
        'name',
        'description',
        'price',
        'discount_price',
        'category',
        'image',
        'is_available',
    ];

    protected $returnType = 'array';
}
