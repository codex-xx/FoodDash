<?php

namespace App\Models;

use CodeIgniter\Model;

class BackupRunModel extends Model
{
    protected $table = 'backup_runs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'file_name',
        'file_path',
        'storage_type',
        'backup_size_bytes',
        'checksum_sha256',
        'status',
        'started_at',
        'finished_at',
        'initiated_by',
        'notes',
    ];
}
