<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail  = 'noreply@fooddash.com';
    public string $fromName   = 'FoodDash';
    public string $recipients = '';

    public string $protocol = 'smtp';
    public string $SMTPHost = 'smtp.gmail.com';
    public int    $SMTPPort = 587;
    public string $SMTPUser = '';
    public string $SMTPPass = '';
    public string $SMTPCrypto = 'tls';
    public bool   $SMTPAutoTLS = true;

    public string $mailType = 'html';
    public string $charset  = 'UTF-8';
    public string $newline  = "\r\n";

    public function __construct()
    {
        parent::__construct();

        $this->fromEmail = $this->firstEnvValue(['email.fromEmail', 'EMAIL_FROM']) ?? $this->fromEmail;
        $this->fromName = $this->firstEnvValue(['email.fromName', 'EMAIL_FROM_NAME']) ?? $this->fromName;
        $this->protocol = $this->firstEnvValue(['email.protocol', 'EMAIL_PROTOCOL']) ?? $this->protocol;
        $this->SMTPHost = $this->firstEnvValue(['email.SMTPHost', 'EMAIL_SMTP_HOST', 'SMTP_HOST']) ?? $this->SMTPHost;
        $this->SMTPPort = $this->firstIntEnvValue(['email.SMTPPort', 'EMAIL_SMTP_PORT', 'SMTP_PORT']) ?? $this->SMTPPort;
        $this->SMTPUser = $this->firstEnvValue(['email.SMTPUser', 'EMAIL_SMTP_USER', 'SMTP_USER']) ?? $this->SMTPUser;
        $this->SMTPPass = $this->firstEnvValue(['email.SMTPPass', 'EMAIL_SMTP_PASS', 'SMTP_PASS']) ?? $this->SMTPPass;
        $this->SMTPCrypto = $this->firstEnvValue(['email.SMTPCrypto', 'EMAIL_SMTP_CRYPTO', 'SMTP_CRYPTO']) ?? $this->SMTPCrypto;
        $this->SMTPAutoTLS = $this->firstBoolEnvValue(['email.SMTPAutoTLS', 'EMAIL_SMTP_AUTOTLS', 'SMTP_AUTOTLS'], $this->SMTPAutoTLS);
    }

    /**
     * @param list<string> $keys
     */
    private function firstEnvValue(array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = env($key);
            if (is_string($value) && $value !== '') {
                return $value;
            }

            $serverValue = getenv($key);
            if ($serverValue !== false && $serverValue !== '') {
                return $serverValue;
            }
        }

        return null;
    }

    /**
     * @param list<string> $keys
     */
    private function firstIntEnvValue(array $keys): ?int
    {
        $value = $this->firstEnvValue($keys);

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param list<string> $keys
     */
    private function firstBoolEnvValue(array $keys, bool $default): bool
    {
        foreach ($keys as $key) {
            $value = env($key);
            if ($value === null || $value === '') {
                $value = getenv($key);
            }

            if ($value === false || $value === null || $value === '') {
                continue;
            }

            if (is_bool($value)) {
                return $value;
            }

            $normalized = strtolower((string) $value);
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return $default;
    }
}