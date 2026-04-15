<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\AuthTokenModel;
use App\Models\UserActivityLogModel;

class SecurityController extends ResourceController
{
	protected $format = 'json';

	public function sessions()
	{
		[$userType, $userId] = $this->resolveActor();
		if ($userType === null || $userId === null) {
			return $this->respond(['success' => false, 'message' => 'Unauthorized'], 401);
		}

		if (! $this->tableExists('auth_tokens')) {
			return $this->respond([
				'success' => true,
				'message' => 'Session tracking table is not available yet.',
				'data' => [],
			]);
		}

		$currentJti = (string) (($this->request->tokenClaims['jti'] ?? '') ?: '');

		$model = new AuthTokenModel();
		$rows = $model->where('user_type', $userType)
			->where('user_id', $userId)
			->orderBy('issued_at', 'DESC')
			->findAll(25);

		$now = date('Y-m-d H:i:s');
		$sessions = [];
		foreach ($rows as $row) {
			$isRevoked = ! empty($row['revoked_at']);
			$isExpired = ! empty($row['expires_at']) && $row['expires_at'] < $now;
			if ($isRevoked || $isExpired) {
				continue;
			}

			$sessions[] = [
				'jti' => $row['jti'],
				'issued_at' => $row['issued_at'],
				'expires_at' => $row['expires_at'],
				'last_seen_at' => $row['last_seen_at'] ?? null,
				'is_current' => $currentJti !== '' && hash_equals($row['jti'], $currentJti),
			];
		}

		return $this->respond([
			'success' => true,
			'data' => $sessions,
		]);
	}

	public function revokeOtherSessions()
	{
		[$userType, $userId] = $this->resolveActor();
		if ($userType === null || $userId === null) {
			return $this->respond(['success' => false, 'message' => 'Unauthorized'], 401);
		}

		if (! $this->tableExists('auth_tokens')) {
			return $this->respond(['success' => false, 'message' => 'Session tracking table is not available yet.'], 400);
		}

		$currentJti = (string) (($this->request->tokenClaims['jti'] ?? '') ?: '');
		if ($currentJti === '') {
			return $this->respond(['success' => false, 'message' => 'Current session cannot be identified.'], 400);
		}

		$model = new AuthTokenModel();
		$model->where('user_type', $userType)
			->where('user_id', $userId)
			->where('revoked_at', null)
			->where('jti !=', $currentJti)
			->set(['revoked_at' => date('Y-m-d H:i:s')])
			->update();

		return $this->respond([
			'success' => true,
			'message' => 'Other active sessions revoked successfully.',
		]);
	}

	public function activityLogs()
	{
		[$userType, $userId] = $this->resolveActor();
		if ($userType === null || $userId === null) {
			return $this->respond(['success' => false, 'message' => 'Unauthorized'], 401);
		}

		if (! $this->tableExists('user_activity_logs')) {
			return $this->respond([
				'success' => true,
				'message' => 'Activity log table is not available yet.',
				'data' => [],
			]);
		}

		$model = new UserActivityLogModel();
		$rows = $model->select('id, activity_type, target_type, target_id, created_at')
			->where('user_type', $userType)
			->where('user_id', $userId)
			->orderBy('id', 'DESC')
			->findAll(50);

		return $this->respond([
			'success' => true,
			'data' => $rows,
		]);
	}

	protected function resolveActor(): array
	{
		$userType = $this->request->userType ?? null;

		if ($userType === 'customer' && isset($this->request->customer['id'])) {
			return ['customer', (int) $this->request->customer['id']];
		}

		if ($userType === 'driver' && isset($this->request->driver['id'])) {
			return ['driver', (int) $this->request->driver['id']];
		}

		return [null, null];
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
