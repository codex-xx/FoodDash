<?php

namespace App\Libraries;

use App\Models\BackupRunModel;
use Config\Database;

class DatabaseBackupService
{
	protected string $backupDir;

	public function __construct()
	{
		$configured = env('backup.path');
		$this->backupDir = is_string($configured) && trim($configured) !== ''
			? rtrim($configured, '\\/')
			: WRITEPATH . 'db_backups';
	}

	public function createBackup(?string $label = null, ?string $initiatedBy = 'system'): array
	{
		$startedAt = date('Y-m-d H:i:s');
		$label = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string) ($label ?: 'manual'));
		$fileName = sprintf('fooddash_%s_%s.sql', $label, date('Ymd_His'));
		$filePath = $this->getBackupDirectory() . DIRECTORY_SEPARATOR . $fileName;

		$runModel = $this->getRunModel();
		$runId = null;

		if ($runModel !== null) {
			$runId = $runModel->insert([
				'file_name' => $fileName,
				'file_path' => $filePath,
				'storage_type' => 'local',
				'status' => 'running',
				'started_at' => $startedAt,
				'initiated_by' => $initiatedBy,
			]);
		}

		$dumpBinary = $this->resolveDumpBinary();
		if ($dumpBinary === null) {
			return $this->finalizeRun($runModel, $runId, false, [
				'message' => 'mysqldump command was not found. Install MySQL client tools or set backup.mysqldumpPath in .env.',
			]);
		}

		$command = $this->buildDumpCommand($dumpBinary, $filePath);
		exec($command, $output, $exitCode);

		if ($exitCode !== 0 || ! is_file($filePath)) {
			$errorMessage = trim(implode(PHP_EOL, $output));
			if ($errorMessage === '') {
				$errorMessage = 'Backup command failed.';
			}

			return $this->finalizeRun($runModel, $runId, false, [
				'message' => $errorMessage,
			]);
		}

		$size = (int) filesize($filePath);
		$checksum = hash_file('sha256', $filePath) ?: null;

		return $this->finalizeRun($runModel, $runId, true, [
			'message' => 'Backup completed successfully.',
			'file_name' => $fileName,
			'file_path' => $filePath,
			'size_bytes' => $size,
			'checksum' => $checksum,
			'started_at' => $startedAt,
			'finished_at' => date('Y-m-d H:i:s'),
		]);
	}

	public function listBackups(): array
	{
		$directory = $this->getBackupDirectory();
		$files = glob($directory . DIRECTORY_SEPARATOR . '*.sql') ?: [];
		rsort($files);

		$items = [];
		foreach ($files as $file) {
			$items[] = [
				'file_name' => basename($file),
				'file_path' => $file,
				'size_bytes' => (int) filesize($file),
				'checksum' => hash_file('sha256', $file) ?: null,
				'created_at' => date('Y-m-d H:i:s', (int) filemtime($file)),
			];
		}

		return $items;
	}

	public function restoreBackup(string $fileName, ?string $initiatedBy = 'system'): array
	{
		$safeFileName = basename($fileName);
		$filePath = $this->getBackupDirectory() . DIRECTORY_SEPARATOR . $safeFileName;

		if (! is_file($filePath)) {
			return [
				'success' => false,
				'message' => 'Backup file was not found: ' . $safeFileName,
			];
		}

		$mysqlBinary = $this->resolveMysqlBinary();
		if ($mysqlBinary === null) {
			return [
				'success' => false,
				'message' => 'mysql command was not found. Install MySQL client tools or set backup.mysqlPath in .env.',
			];
		}

		$command = $this->buildRestoreCommand($mysqlBinary, $filePath);
		exec($command, $output, $exitCode);

		if ($exitCode !== 0) {
			$errorMessage = trim(implode(PHP_EOL, $output));
			if ($errorMessage === '') {
				$errorMessage = 'Restore command failed.';
			}

			return [
				'success' => false,
				'message' => $errorMessage,
				'file_name' => $safeFileName,
			];
		}

		return [
			'success' => true,
			'message' => 'Restore completed successfully.',
			'file_name' => $safeFileName,
			'initiated_by' => $initiatedBy,
			'restored_at' => date('Y-m-d H:i:s'),
		];
	}

	protected function getBackupDirectory(): string
	{
		if (! is_dir($this->backupDir)) {
			mkdir($this->backupDir, 0775, true);
		}

		return $this->backupDir;
	}

	protected function buildDumpCommand(string $dumpBinary, string $outputFile): string
	{
		$db = $this->getDbConfig();

		$parts = [
			escapeshellarg($dumpBinary),
			'--single-transaction',
			'--quick',
			'--routines',
			'--triggers',
			'--host=' . escapeshellarg((string) ($db['hostname'] ?? 'localhost')),
			'--port=' . (int) ($db['port'] ?? 3306),
			'--user=' . escapeshellarg((string) ($db['username'] ?? 'root')),
		];

		$password = (string) ($db['password'] ?? '');
		if ($password !== '') {
			$parts[] = '--password=' . escapeshellarg($password);
		}

		$parts[] = escapeshellarg((string) ($db['database'] ?? 'fooddash_db'));

		return implode(' ', $parts) . ' > ' . escapeshellarg($outputFile) . ' 2>&1';
	}

	protected function buildRestoreCommand(string $mysqlBinary, string $sourceFile): string
	{
		$db = $this->getDbConfig();

		$parts = [
			escapeshellarg($mysqlBinary),
			'--host=' . escapeshellarg((string) ($db['hostname'] ?? 'localhost')),
			'--port=' . (int) ($db['port'] ?? 3306),
			'--user=' . escapeshellarg((string) ($db['username'] ?? 'root')),
		];

		$password = (string) ($db['password'] ?? '');
		if ($password !== '') {
			$parts[] = '--password=' . escapeshellarg($password);
		}

		$parts[] = escapeshellarg((string) ($db['database'] ?? 'fooddash_db'));

		return implode(' ', $parts) . ' < ' . escapeshellarg($sourceFile) . ' 2>&1';
	}

	protected function resolveDumpBinary(): ?string
	{
		$configured = env('backup.mysqldumpPath');
		if (is_string($configured) && trim($configured) !== '') {
			return $configured;
		}

		$xamppDefault = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
		if (is_file($xamppDefault)) {
			return $xamppDefault;
		}

		return $this->binaryExists('mysqldump') ? 'mysqldump' : null;
	}

	protected function resolveMysqlBinary(): ?string
	{
		$configured = env('backup.mysqlPath');
		if (is_string($configured) && trim($configured) !== '') {
			return $configured;
		}

		$xamppDefault = 'C:\\xampp\\mysql\\bin\\mysql.exe';
		if (is_file($xamppDefault)) {
			return $xamppDefault;
		}

		return $this->binaryExists('mysql') ? 'mysql' : null;
	}

	protected function binaryExists(string $binary): bool
	{
		$command = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where ' : 'command -v ';
		exec($command . escapeshellarg($binary), $output, $code);
		return $code === 0;
	}

	protected function getDbConfig(): array
	{
		$databaseConfig = new Database();
		return $databaseConfig->default;
	}

	protected function getRunModel(): ?BackupRunModel
	{
		if (! db_connect()->tableExists('backup_runs')) {
			return null;
		}

		return new BackupRunModel();
	}

	protected function finalizeRun(?BackupRunModel $runModel, $runId, bool $success, array $payload): array
	{
		$status = $success ? 'success' : 'failed';
		$finishedAt = date('Y-m-d H:i:s');

		if ($runModel !== null && $runId) {
			$runModel->update((int) $runId, [
				'status' => $status,
				'finished_at' => $finishedAt,
				'backup_size_bytes' => (int) ($payload['size_bytes'] ?? 0),
				'checksum_sha256' => $payload['checksum'] ?? null,
				'notes' => $payload['message'] ?? null,
			]);
		}

		$payload['success'] = $success;
		$payload['status'] = $status;
		$payload['finished_at'] = $payload['finished_at'] ?? $finishedAt;

		return $payload;
	}
}
