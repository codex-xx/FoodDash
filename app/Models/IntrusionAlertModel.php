<?php

namespace App\Models;

use CodeIgniter\Model;

class IntrusionAlertModel extends Model
{
    protected $table = 'intrusion_alerts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'audit_log_id',
        'user_id',
        'ip_address_hash',
        'alert_type',
        'status',
        'severity',
        'alert_message',
        'trigger_count',
        'triggered_at',
        'resolved_at',
    ];
}
