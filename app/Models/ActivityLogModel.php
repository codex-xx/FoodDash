<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table = 'activity_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useTimestamps = false;

    protected $allowedFields = [
        'actor_user_id',
        'action',
        'entity_type',
        'entity_id',
        'status',
        'message',
        'context_json',
        'ip_hash',
        'created_at',
    ];
}
