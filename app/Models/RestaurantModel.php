<?php

namespace App\Models;

use CodeIgniter\Model;

class RestaurantModel extends Model
{
    protected $table = 'restaurants';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'user_id',
        'name',
        'address',
        'logo',
        'opening_hours',
        'status',
        'is_active',
    ];

    protected $returnType = 'array';
}
