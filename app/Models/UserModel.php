<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'email',
        'password',
        'role',
        'is_active',
        'reset_token',
        'reset_expires',
        'mfa_enabled',
        'login_otp_code',
        'login_otp_expires',
        'token_version',
    ];

    protected $returnType = 'array';

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
            $data['data']['password'] = password_hash($data['data']['password'], $algo);
        }

        return $data;
    }
}
