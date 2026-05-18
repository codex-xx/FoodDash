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
        'restaurant_address',
        'latitude',
        'longitude',
        'restaurant_latitude',
        'restaurant_longitude',
        'delivery_radius_km',
        'logo',
        'opening_hours',
        'status',
        'is_active',
        'is_open',
    ];

    protected $returnType = 'array';
}
