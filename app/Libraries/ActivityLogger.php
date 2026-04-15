<?php

namespace App\Libraries;

use App\Models\UserActivityLogModel;
use App\Models\LoginActivityModel;
use CodeIgniter\HTTP\IncomingRequest;

class ActivityLogger
{
	protected SensitiveDataService $sensitive;

	public function __construct()
	{
		$this->sensitive = new SensitiveDataService();
	}

	public function logLoginAttempt(
		IncomingRequest $request,
		?string $userType,
		?int $userId,
		?string $email,
		bool $success,
		?string $reason = null
	): void {
		if (! $this->tableExists('login_activities')) {
			return;
		}

		$meta = [
			'event' => 'login_attempt',
			'success' => $success,
			'reason' => $success ? null : ($reason ?? 'login_failed'),
			'email' => $this->sensitive->normalize($email),
			'ip_address' => (string) $request->getIPAddress(),
			'user_agent' => substr((string) $request->getUserAgent(), 0, 255),
			'path' => (string) $request->getUri()->getPath(),
			'method' => (string) $request->getMethod(),
			'logged_at' => date('c'),
		];

		$encodedMeta = $this->encodeMeta($meta);
		$hasEncryptedMeta = $this->tableHasColumn('login_activities', 'event_meta_encrypted')
			&& $this->tableHasColumn('login_activities', 'event_meta_hash');
		$hasActorColumns = $this->tableHasColumn('login_activities', 'actor_type')
			&& $this->tableHasColumn('login_activities', 'actor_id');
		$hasStatusColumns = $this->tableHasColumn('login_activities', 'login_status')
			&& $this->tableHasColumn('login_activities', 'reason');

		try {
			$model = new LoginActivityModel();
			$payload = [
				'email_hash' => $this->sensitive->hashForLookup($email),
				'ip_address' => $hasEncryptedMeta ? null : (string) $request->getIPAddress(),
				'user_agent' => $hasEncryptedMeta ? null : substr((string) $request->getUserAgent(), 0, 255),
			];

			if ($hasActorColumns) {
				$payload['actor_type'] = $userType;
				$payload['actor_id'] = $userId;
			} else {
				$payload['user_type'] = $userType;
				$payload['user_id'] = $userId;
			}

			if ($hasStatusColumns) {
				$payload['login_status'] = $success ? 'success' : 'failed';
				$payload['reason'] = $success ? null : ($reason ?? 'login_failed');
			} else {
				$payload['success'] = $success ? 1 : 0;
				$payload['failure_reason'] = $success ? null : ($reason ?? 'login_failed');
				$payload['login_at'] = date('Y-m-d H:i:s');
			}

			if ($hasEncryptedMeta) {
				$payload['event_meta_encrypted'] = $this->sensitive->encryptNullable($encodedMeta);
				$payload['event_meta_hash'] = $this->sensitive->hashExact($encodedMeta);
			}

			$model->insert($payload);
		} catch (\Throwable $e) {
			log_message('error', 'Unable to write login activity: ' . $e->getMessage());
		}
	}

	public function logUserActivity(
		IncomingRequest $request,
		string $userType,
		int $userId,
		string $activityType,
		?string $targetType = null,
		?int $targetId = null,
		array $meta = []
	): void {
		if (! $this->tableExists('user_activity_logs')) {
			return;
		}

		$enrichedMeta = [
			'activity' => $activityType,
			'target_type' => $targetType,
			'target_id' => $targetId,
			'ip_address' => (string) $request->getIPAddress(),
			'user_agent' => substr((string) $request->getUserAgent(), 0, 255),
			'path' => (string) $request->getUri()->getPath(),
			'method' => (string) $request->getMethod(),
			'logged_at' => date('c'),
			'changes' => $meta,
		];

		$encodedMeta = $this->encodeMeta($enrichedMeta);

		try {
			$model = new UserActivityLogModel();
			$model->insert([
				'user_type' => $userType,
				'user_id' => $userId,
				'activity_type' => $activityType,
				'target_type' => $targetType,
				'target_id' => $targetId,
				'activity_meta_encrypted' => $this->sensitive->encryptNullable($encodedMeta),
				'activity_meta_hash' => $this->sensitive->hashExact($encodedMeta),
				'ip_hash' => $this->sensitive->hashForLookup((string) $request->getIPAddress()),
				'user_agent_hash' => $this->sensitive->hashForLookup(substr((string) $request->getUserAgent(), 0, 255)),
			]);
		} catch (\Throwable $e) {
			log_message('error', 'Unable to write user activity log: ' . $e->getMessage());
		}
	}

	protected function tableExists(string $table): bool
	{
		try {
			return db_connect()->tableExists($table);
		} catch (\Throwable $e) {
			return false;
		}
	}

	protected function tableHasColumn(string $table, string $column): bool
	{
		try {
			return db_connect()->fieldExists($column, $table);
		} catch (\Throwable $e) {
			return false;
		}
	}

	protected function encodeMeta(array $payload): string
	{
		$json = json_encode($payload, JSON_UNESCAPED_SLASHES);
		return is_string($json) ? $json : '{}';
	}
}
