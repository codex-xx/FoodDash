<?php

namespace App\Models;

use CodeIgniter\Model;

class DriverModel extends Model
{
    protected $table = 'drivers';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'user_id',
        'name',
        'email',
        'password',
        'phone',
        'vehicle_type',
        'vehicle_number',
        'license_number',
        'profile_image',
        'current_latitude',
        'current_longitude',
        'status',
        'is_active',
        'api_token',
        'fcm_token',
    ];

    protected $returnType = 'array';
}
