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
		'actor_type',
		'actor_id',
		'user_type',
		'user_id',
		'jwt_id',
		'jti',
		'token_hash',
		'issued_at',
		'expires_at',
		'last_seen_at',
		'revoked_at',
		'ip_address',
		'user_agent',
	];
}
