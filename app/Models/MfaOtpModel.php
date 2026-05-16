<?php

namespace App\Models;

use CodeIgniter\Model;

class MfaOtpModel extends Model
{
    protected $table = 'mfa_otps';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $allowedFields = [
        'user_id',
        'email',
        'challenge_token',
        'otp_hash',
        'purpose',
        'attempts',
        'expires_at',
        'consumed_at',
    ];
}