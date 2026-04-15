<?php

namespace App\Libraries;

use App\Models\AuditLogModel;
use App\Models\BlockedIpModel;
use App\Models\IntrusionAlertModel;
use CodeIgniter\HTTP\IncomingRequest;
use Config\IntrusionDetection;

class SecurityAuditService
{
    protected SensitiveDataService $sensitive;
    protected IntrusionDetection $config;

    public function __construct()
    {
        $this->sensitive = new SensitiveDataService();
        $this->config = config('IntrusionDetection');
    }

    public function logEvent(
        IncomingRequest $request,
        ?int $userId,
        string $eventType,
        string $description,
        string $severity = 'medium',
        array $meta = []
    ): ?int {
        if (! $this->tableExists('audit_logs')) {
            return null;
        }

        $severity = $this->normalizeSeverity($severity);
        $ipHash = $this->sensitive->hashForLookup((string) $request->getIPAddress());
        $deviceHash = $this->sensitive->hashForLookup(substr((string) $request->getUserAgent(), 0, 255));

        $payload = [
            'event_type' => $eventType,
            'severity' => $severity,
            'description' => $description,
            'path' => (string) $request->getUri()->getPath(),
            'method' => (string) $request->getMethod(),
            'ip_hash' => $ipHash,
            'device_hash' => $deviceHash,
            'meta' => $meta,
            'logged_at' => date('c'),
        ];

        $encoded = $this->encode($payload);
        $prevHash = $this->latestRecordHash();
        $recordHash = $this->computeRecordHash($userId, $ipHash, $eventType, $description, $encoded, $prevHash);

        $model = new AuditLogModel();
        $model->insert([
            'user_id' => $userId,
            'ip_address_hash' => $ipHash,
            'device_hash' => $deviceHash,
            'event_type' => $eventType,
            'event_description' => $description,
            'severity' => $severity,
            'event_meta_encrypted' => $this->sensitive->encryptNullable($encoded),
            'event_meta_hash' => $this->sensitive->hashExact($encoded),
            'prev_hash' => $prevHash,
            'record_hash' => $recordHash,
        ]);

        $insertId = $model->getInsertID();
        return is_numeric($insertId) ? (int) $insertId : null;
    }

    public function isRequestBlocked(IncomingRequest $request): bool
    {
        if (! $this->tableExists('blocked_ips')) {
            return false;
        }

        $ipHash = $this->sensitive->hashForLookup((string) $request->getIPAddress());
        if ($ipHash === null) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $model = new BlockedIpModel();

        $activeBlock = $model
            ->where('ip_address_hash', $ipHash)
            ->where('is_active', 1)
            ->groupStart()
            ->where('blocked_until', null)
            ->orWhere('blocked_until >', $now)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->first();

        if ($activeBlock !== null) {
            return true;
        }

        $model
            ->where('ip_address_hash', $ipHash)
            ->where('is_active', 1)
            ->where('blocked_until <=', $now)
            ->set(['is_active' => 0, 'updated_at' => $now])
            ->update();

        return false;
    }

    public function requiresCaptcha(IncomingRequest $request): bool
    {
        if (! $this->tableExists('audit_logs')) {
            return false;
        }

        $ipHash = $this->sensitive->hashForLookup((string) $request->getIPAddress());
        if ($ipHash === null) {
            return false;
        }

        $since = date('Y-m-d H:i:s', time() - $this->config->captchaWindowSeconds);

        $count = (new AuditLogModel())
            ->where('event_type', 'failed_login')
            ->where('ip_address_hash', $ipHash)
            ->where('created_at >=', $since)
            ->countAllResults();

        return $count >= $this->config->captchaThreshold;
    }

    public function recordFailedLogin(IncomingRequest $request, ?int $userId, string $reason, ?string $email = null): array
    {
        $auditId = $this->logEvent(
            $request,
            $userId,
            'failed_login',
            'Failed login attempt detected',
            'medium',
            [
                'reason' => $reason,
                'email_hash' => $this->sensitive->hashForLookup($email),
            ]
        );

        $ipHash = $this->sensitive->hashForLookup((string) $request->getIPAddress());
        $alertRaised = false;
        $isBlocked = false;

        if ($ipHash !== null) {
            $since = date('Y-m-d H:i:s', time() - $this->config->failedLoginWindowSeconds);

            $count = (new AuditLogModel())
                ->where('event_type', 'failed_login')
                ->where('ip_address_hash', $ipHash)
                ->where('created_at >=', $since)
                ->countAllResults();

            if ($count === $this->config->captchaThreshold) {
                $this->raiseAlert(
                    $auditId,
                    $userId,
                    $ipHash,
                    'captcha_challenge_required',
                    'medium',
                    'Repeated failed login attempts now require CAPTCHA verification',
                    $count
                );
                $alertRaised = true;
            }

            if ($count >= $this->config->failedLoginThreshold) {
                $this->raiseAlert(
                    $auditId,
                    $userId,
                    $ipHash,
                    'failed_login_threshold',
                    'critical',
                    'Multiple failed login attempts exceeded threshold',
                    $count
                );
                $alertRaised = true;

                $this->blockIpHash($ipHash, 'Automatic temporary block due to repeated failed logins');
                $isBlocked = true;
            }
        }

        return [
            'alert_raised' => $alertRaised,
            'blocked' => $isBlocked,
            'captcha_required' => $this->requiresCaptcha($request),
        ];
    }

    public function recordSuccessfulLogin(IncomingRequest $request, int $userId): void
    {
        $auditId = $this->logEvent(
            $request,
            $userId,
            'login_success',
            'Successful login',
            'low'
        );

        $this->detectLocationOrDeviceAnomaly($request, $userId, $auditId);
    }

    public function recordUnauthorizedAccess(IncomingRequest $request, ?int $userId, string $description): void
    {
        $auditId = $this->logEvent(
            $request,
            $userId,
            'unauthorized_access',
            $description,
            'high'
        );

        if (! $this->tableExists('audit_logs')) {
            return;
        }

        $ipHash = $this->sensitive->hashForLookup((string) $request->getIPAddress());
        if ($ipHash === null) {
            return;
        }

        $since = date('Y-m-d H:i:s', time() - $this->config->unauthorizedWindowSeconds);

        $count = (new AuditLogModel())
            ->where('event_type', 'unauthorized_access')
            ->where('ip_address_hash', $ipHash)
            ->where('created_at >=', $since)
            ->countAllResults();

        if ($count >= $this->config->unauthorizedThreshold) {
            $this->raiseAlert(
                $auditId,
                $userId,
                $ipHash,
                'repeated_unauthorized_access',
                'high',
                'Repeated unauthorized access attempts detected',
                $count
            );
        }
    }

    public function recentAlerts(int $limit = 20): array
    {
        if (! $this->tableExists('intrusion_alerts')) {
            return [];
        }

        return (new IntrusionAlertModel())
            ->orderBy('triggered_at', 'DESC')
            ->findAll($limit);
    }

    public function activeBlocks(int $limit = 20): array
    {
        if (! $this->tableExists('blocked_ips')) {
            return [];
        }

        $now = date('Y-m-d H:i:s');

        return (new BlockedIpModel())
            ->where('is_active', 1)
            ->groupStart()
            ->where('blocked_until', null)
            ->orWhere('blocked_until >', $now)
            ->groupEnd()
            ->orderBy('blocked_at', 'DESC')
            ->findAll($limit);
    }

    public function buildReportSummary(string $period): array
    {
        if (! $this->tableExists('audit_logs')) {
            return [
                'period' => $period,
                'failed_login_attempts' => 0,
                'intrusion_attempts' => 0,
                'blocked_ip_events' => 0,
                'system_vulnerabilities_detected' => 0,
                'generated_at' => date('Y-m-d H:i:s'),
            ];
        }

        $period = strtolower($period);
        $seconds = match ($period) {
            'daily' => 86400,
            'weekly' => 604800,
            'monthly' => 2592000,
            default => 86400,
        };

        $since = date('Y-m-d H:i:s', time() - $seconds);

        $auditModel = new AuditLogModel();
        $alertsModel = $this->tableExists('intrusion_alerts') ? new IntrusionAlertModel() : null;
        $blockModel = $this->tableExists('blocked_ips') ? new BlockedIpModel() : null;

        $failedLogins = $auditModel
            ->where('event_type', 'failed_login')
            ->where('created_at >=', $since)
            ->countAllResults();

        $intrusions = $alertsModel
            ? $alertsModel
                ->where('triggered_at >=', $since)
                ->countAllResults()
            : 0;

        $blocked = $blockModel
            ? $blockModel
                ->where('blocked_at >=', $since)
                ->countAllResults()
            : 0;

        $vulnerabilities = $alertsModel
            ? $alertsModel
                ->where('severity', 'critical')
                ->where('triggered_at >=', $since)
                ->countAllResults()
            : 0;

        return [
            'period' => $period,
            'failed_login_attempts' => (int) $failedLogins,
            'intrusion_attempts' => (int) $intrusions,
            'blocked_ip_events' => (int) $blocked,
            'system_vulnerabilities_detected' => (int) $vulnerabilities,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    protected function detectLocationOrDeviceAnomaly(IncomingRequest $request, int $userId, ?int $auditId): void
    {
        if (! $this->tableExists('auth_tokens')) {
            return;
        }

        $windowStart = date('Y-m-d H:i:s', time() - $this->config->locationDeviceAnomalyWindowSeconds);
        $currentIp = (string) $request->getIPAddress();
        $currentAgent = substr((string) $request->getUserAgent(), 0, 255);
        $db = db_connect();

        $userIdColumn = $db->fieldExists('user_id', 'auth_tokens') ? 'user_id' : 'actor_id';
        if (! $db->fieldExists($userIdColumn, 'auth_tokens')) {
            return;
        }

        $issuedColumn = $db->fieldExists('issued_at', 'auth_tokens') ? 'issued_at' : 'created_at';
        $hasIssuedColumn = $db->fieldExists($issuedColumn, 'auth_tokens');

        $query = $db->table('auth_tokens')
            ->select('ip_address, user_agent' . ($hasIssuedColumn ? ', ' . $issuedColumn . ' as issued_at' : ''))
            ->where($userIdColumn, $userId)
            ->orderBy('id', 'DESC')
            ->limit(5);

        if ($hasIssuedColumn) {
            $query->where($issuedColumn . ' >=', $windowStart);
        }

        $tokens = $query->get()->getResultArray();

        foreach ($tokens as $token) {
            $ipChanged = (string) ($token['ip_address'] ?? '') !== ''
                && (string) $token['ip_address'] !== $currentIp;
            $deviceChanged = (string) ($token['user_agent'] ?? '') !== ''
                && (string) $token['user_agent'] !== $currentAgent;

            if ($ipChanged || $deviceChanged) {
                $ipHash = $this->sensitive->hashForLookup($currentIp);
                $this->raiseAlert(
                    $auditId,
                    $userId,
                    $ipHash,
                    'location_or_device_anomaly',
                    'high',
                    'Login from different location/device detected in a short period',
                    1
                );

                $this->logEvent(
                    $request,
                    $userId,
                    'suspicious_account_behavior',
                    'Account accessed from different IP/device in a short time',
                    'high'
                );
                break;
            }
        }
    }

    protected function blockIpHash(string $ipHash, string $reason): void
    {
        if (! $this->tableExists('blocked_ips')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $until = date('Y-m-d H:i:s', time() + $this->config->blockDurationSeconds);
        $model = new BlockedIpModel();

        $existing = $model
            ->where('ip_address_hash', $ipHash)
            ->where('is_active', 1)
            ->orderBy('id', 'DESC')
            ->first();

        if ($existing !== null) {
            $model->update((int) $existing['id'], [
                'reason' => $reason,
                'blocked_at' => $now,
                'blocked_until' => $until,
                'is_active' => 1,
            ]);
            return;
        }

        $model->insert([
            'ip_address_hash' => $ipHash,
            'reason' => $reason,
            'blocked_at' => $now,
            'blocked_until' => $until,
            'is_active' => 1,
        ]);
    }

    protected function raiseAlert(
        ?int $auditLogId,
        ?int $userId,
        ?string $ipHash,
        string $alertType,
        string $severity,
        string $message,
        int $triggerCount
    ): void {
        if (! $this->tableExists('intrusion_alerts')) {
            return;
        }

        $model = new IntrusionAlertModel();
        $now = date('Y-m-d H:i:s');

        $model->insert([
            'audit_log_id' => $auditLogId,
            'user_id' => $userId,
            'ip_address_hash' => $ipHash,
            'alert_type' => $alertType,
            'status' => 'open',
            'severity' => $severity,
            'alert_message' => $message,
            'trigger_count' => max(1, $triggerCount),
            'triggered_at' => $now,
        ]);

        $this->sendEmailAlert($severity, $alertType, $message, $triggerCount);
    }

    protected function sendEmailAlert(string $severity, string $alertType, string $message, int $triggerCount): void
    {
        $to = $this->config->adminAlertEmail;
        if (! is_string($to) || trim($to) === '') {
            return;
        }

        try {
            $email = service('email');
            $email->setTo($to);
            $email->setSubject('[FoodDash Security Alert] ' . strtoupper($severity) . ' - ' . $alertType);
            $email->setMessage(
                "Security alert triggered.\n\n"
                . 'Type: ' . $alertType . "\n"
                . 'Severity: ' . $severity . "\n"
                . 'Trigger count: ' . $triggerCount . "\n"
                . 'Message: ' . $message . "\n"
                . 'Triggered at: ' . date('Y-m-d H:i:s')
            );
            $email->send();
        } catch (\Throwable $e) {
            log_message('error', 'Security alert email failed: ' . $e->getMessage());
        }
    }

    protected function latestRecordHash(): ?string
    {
        if (! $this->tableExists('audit_logs')) {
            return null;
        }

        $row = (new AuditLogModel())
            ->select('record_hash')
            ->orderBy('id', 'DESC')
            ->first();

        return is_array($row) ? (string) ($row['record_hash'] ?? '') ?: null : null;
    }

    protected function computeRecordHash(?int $userId, ?string $ipHash, string $eventType, string $description, string $meta, ?string $prevHash): string
    {
        $parts = [
            (string) ($userId ?? ''),
            (string) ($ipHash ?? ''),
            $eventType,
            $description,
            $meta,
            (string) ($prevHash ?? ''),
            date('Y-m-d H:i:s'),
        ];

        return hash('sha256', implode('|', $parts));
    }

    protected function normalizeSeverity(string $severity): string
    {
        $severity = strtolower(trim($severity));
        if (in_array($severity, ['low', 'medium', 'high', 'critical'], true)) {
            return $severity;
        }

        return 'medium';
    }

    protected function encode(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : '{}';
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
