<?php

namespace App\Models;

use CodeIgniter\Model;

class DriverModel extends Model
{
    protected $table = 'drivers';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'user_id',
        'name',
        'email',
        'password',
        'phone',
        'vehicle_type',
        'license_number',
        'profile_image',
        'status',
        'is_active',
        'api_token',
        'fcm_token',
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
