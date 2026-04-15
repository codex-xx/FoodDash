<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'user_id',
        'ip_address_hash',
        'device_hash',
        'event_type',
        'event_description',
        'severity',
        'event_meta_encrypted',
        'event_meta_hash',
        'prev_hash',
        'record_hash',
    ];
}
