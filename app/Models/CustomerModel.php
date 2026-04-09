<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'profile_image',
        'api_token',
        'fcm_token',
        'reset_token',
        'reset_expires',
        'reset_code',
        'mfa_enabled',
        'login_otp_code',
        'login_otp_expires',
        'token_version',
        'is_active',
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

    public function generateApiToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
