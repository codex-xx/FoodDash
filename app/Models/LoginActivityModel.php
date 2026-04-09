<?php

namespace App\Models;

use CodeIgniter\Model;

class LoginActivityModel extends Model
{
	protected $table = 'login_activities';
	protected $primaryKey = 'id';
	protected $returnType = 'array';

	protected $useTimestamps = true;
	protected $createdField = 'created_at';
	protected $updatedField = 'updated_at';

	protected $allowedFields = [
		'user_type',
		'user_id',
		'email_hash',
		'ip_address',
		'user_agent',
		'success',
		'failure_reason',
		'login_at',
	];
}
