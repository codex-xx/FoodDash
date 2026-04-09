<?php

namespace App\Models;

use App\Libraries\SensitiveDataService;
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
        'email_encrypted',
        'email_hash',
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

    protected $beforeInsert = ['hashPassword', 'protectSensitiveFields'];
    protected $beforeUpdate = ['hashPassword', 'protectSensitiveFields'];

    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
            $data['data']['password'] = password_hash($data['data']['password'], $algo);
        }

        return $data;
    }

    protected function protectSensitiveFields(array $data): array
    {
        if (! isset($data['data']) || ! is_array($data['data'])) {
            return $data;
        }

        if (array_key_exists('email', $data['data'])) {
            $sensitive = new SensitiveDataService();
            $data['data']['email_hash'] = $sensitive->hashForLookup((string) $data['data']['email']);
            $data['data']['email_encrypted'] = $sensitive->encryptNullable((string) $data['data']['email']);
        }

        return $data;
    }
}
