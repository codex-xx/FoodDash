<?php

namespace App\Models;

use CodeIgniter\Model;

class BlockedIpModel extends Model
{
    protected $table = 'blocked_ips';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'ip_address_hash',
        'reason',
        'blocked_at',
        'blocked_until',
        'is_active',
    ];
}
