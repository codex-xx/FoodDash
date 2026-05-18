<?php

namespace App\Models;

use App\Libraries\SensitiveDataService;
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
        'email_encrypted',
        'email_hash',
        'password',
        'phone',
        'phone_encrypted',
        'phone_hash',
        'vehicle_type',
        'license_number',
        'address',
        'latitude',
        'longitude',
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

        $sensitive = new SensitiveDataService();

        if (array_key_exists('email', $data['data'])) {
            $data['data']['email_hash'] = $sensitive->hashForLookup((string) $data['data']['email']);
            $data['data']['email_encrypted'] = $sensitive->encryptNullable((string) $data['data']['email']);
        }

        if (array_key_exists('phone', $data['data'])) {
            $data['data']['phone_hash'] = $sensitive->hashForLookup((string) $data['data']['phone']);
            $data['data']['phone_encrypted'] = $sensitive->encryptNullable((string) $data['data']['phone']);
        }

        return $data;
    }
}
