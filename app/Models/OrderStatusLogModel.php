<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderStatusLogModel extends Model
{
    protected $table = 'order_status_logs';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'order_id',
        'from_status',
        'to_status',
        'changed_by_role',
        'changed_by_id',
        'notes',
    ];

    protected $returnType = 'array';
}
