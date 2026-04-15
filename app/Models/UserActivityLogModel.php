<?php

namespace App\Models;

use CodeIgniter\Model;

class UserActivityLogModel extends Model
{
	protected $table = 'user_activity_logs';
	protected $primaryKey = 'id';
	protected $returnType = 'array';

	protected $useTimestamps = true;
	protected $createdField = 'created_at';
	protected $updatedField = 'updated_at';

	protected $allowedFields = [
		'user_type',
		'user_id',
		'activity_type',
		'target_type',
		'target_id',
		'activity_meta_encrypted',
		'activity_meta_hash',
		'ip_hash',
		'user_agent_hash',
	];
}
