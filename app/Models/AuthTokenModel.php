<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthTokenModel extends Model
{
	protected $table = 'auth_tokens';
	protected $primaryKey = 'id';
	protected $returnType = 'array';

	protected $useTimestamps = true;
	protected $createdField = 'created_at';
	protected $updatedField = 'updated_at';

	protected $allowedFields = [
		'user_type',
		'user_id',
		'jti',
		'token_hash',
		'issued_at',
		'expires_at',
		'revoked_at',
		'ip_address',
		'user_agent',
	];
}
