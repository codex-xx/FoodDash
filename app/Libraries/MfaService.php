<?php

namespace App\Libraries;

use App\Models\AppSettingModel;
use App\Models\MfaOtpModel;

class MfaService
{
    protected AppSettingModel $settingsModel;
    protected MfaOtpModel $otpModel;
    protected int $otpTtlSeconds = 300;
    protected int $maxAttempts = 5;

    public function __construct()
    {
        $this->settingsModel = new AppSettingModel();
        $this->otpModel = new MfaOtpModel();
    }

    public function isEnabled(): bool
    {
        if (! $this->tableExists('app_settings')) {
            return false;
        }

        return $this->settingsModel->isEnabled('mfa_enabled', false);
    }

    public function setEnabled(bool $enabled): bool
    {
        return $this->settingsModel->setValue('mfa_enabled', $enabled);
    }

    public function getOtpTtlSeconds(): int
    {
        return $this->otpTtlSeconds;
    }

    public function createLoginChallenge(array $user): array
    {
        if (! $this->tableExists('mfa_otps')) {
            return [
                'success' => false,
                'message' => 'MFA storage is unavailable.',
            ];
        }

        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'A valid email address is required for MFA.',
            ];
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return [
                'success' => false,
                'message' => 'A valid user is required for MFA.',
            ];
        }

        $otp = $this->generateOtp();
        $challengeToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $this->otpTtlSeconds);

        try {
            $this->otpModel->where('user_id', $userId)
                ->where('purpose', 'login')
                ->delete();

            $inserted = $this->otpModel->insert([
                'user_id' => $userId,
                'email' => $email,
                'challenge_token' => $challengeToken,
                'otp_hash' => password_hash($otp, PASSWORD_DEFAULT),
                'purpose' => 'login',
                'attempts' => 0,
                'expires_at' => $expiresAt,
            ]);

            if (! $inserted) {
                return [
                    'success' => false,
                    'message' => 'Unable to create MFA challenge.',
                ];
            }

            $emailService = new EmailService();
            $sent = $emailService->sendLoginOtp(
                $email,
                trim((string) ($user['name'] ?? '')) !== '' ? (string) $user['name'] : 'User',
                $otp
            );

            if (! $sent) {
                $this->otpModel->where('challenge_token', $challengeToken)->delete();

                return [
                    'success' => false,
                    'message' => 'Failed to send the verification code. Please try again.',
                ];
            }

            return [
                'success' => true,
                'challenge_token' => $challengeToken,
                'email' => $email,
                'expires_at' => $expiresAt,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Unable to create MFA challenge: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Unable to send the MFA code right now.',
            ];
        }
    }

    public function verifyLoginChallenge(string $challengeToken, int $userId, string $email, string $otp): array
    {
        if (! preg_match('/^\d{6}$/', $otp)) {
            return [
                'success' => false,
                'message' => 'Enter a valid 6-digit verification code.',
            ];
        }

        if (! $this->tableExists('mfa_otps')) {
            return [
                'success' => false,
                'message' => 'MFA storage is unavailable.',
            ];
        }

        try {
            $challenge = $this->otpModel
                ->where('challenge_token', $challengeToken)
                ->where('user_id', $userId)
                ->where('email', $email)
                ->where('purpose', 'login')
                ->first();

            if (! is_array($challenge)) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired verification code.',
                ];
            }

            $expiresAt = strtotime((string) ($challenge['expires_at'] ?? ''));
            if ($expiresAt === false || $expiresAt < time()) {
                $this->otpModel->update((int) $challenge['id'], [
                    'consumed_at' => date('Y-m-d H:i:s'),
                ]);

                return [
                    'success' => false,
                    'message' => 'This verification code has expired. Please sign in again.',
                ];
            }

            if (! password_verify($otp, (string) ($challenge['otp_hash'] ?? ''))) {
                $attempts = (int) ($challenge['attempts'] ?? 0) + 1;
                $update = ['attempts' => $attempts];

                if ($attempts >= $this->maxAttempts) {
                    $update['consumed_at'] = date('Y-m-d H:i:s');
                }

                $this->otpModel->update((int) $challenge['id'], $update);

                if ($attempts >= $this->maxAttempts) {
                    return [
                        'success' => false,
                        'message' => 'Too many invalid attempts. Please sign in again to request a new code.',
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Invalid verification code. Please try again.',
                ];
            }

            $this->otpModel->update((int) $challenge['id'], [
                'consumed_at' => date('Y-m-d H:i:s'),
            ]);

            return [
                'success' => true,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Unable to verify MFA challenge: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Unable to verify the code right now.',
            ];
        }
    }

    public function clearLoginChallenge(string $challengeToken): void
    {
        if (! $this->tableExists('mfa_otps')) {
            return;
        }

        try {
            $this->otpModel->where('challenge_token', $challengeToken)->delete();
        } catch (\Throwable $e) {
            log_message('error', 'Unable to clear MFA challenge: ' . $e->getMessage());
        }
    }

    protected function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    protected function tableExists(string $table): bool
    {
        try {
            return db_connect()->tableExists($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}