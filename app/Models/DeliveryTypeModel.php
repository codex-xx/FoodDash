<?php

namespace App\Models;

use CodeIgniter\Model;

class DeliveryTypeModel extends Model
{
    protected $table = 'delivery_types';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'name',
        'vehicle_type',
        'size_category',
        'is_active',
    ];

    protected $returnType = 'array';
}
