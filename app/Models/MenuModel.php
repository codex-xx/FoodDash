<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuModel extends Model
{
    protected $table = 'menus';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'restaurant_id',
        'name',
        'description',
        'price',
        'image_url',
        'category',
        'availability',
    ];

    protected $returnType = 'array';
}
