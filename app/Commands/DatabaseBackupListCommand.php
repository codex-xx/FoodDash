<?php

namespace App\Commands;

use App\Libraries\DatabaseBackupService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DatabaseBackupListCommand extends BaseCommand
{
	protected $group = 'Database';
	protected $name = 'db:backups';
	protected $description = 'List available SQL backup files.';

	public function run(array $params)
	{
		$service = new DatabaseBackupService();
		$backups = $service->listBackups();

		if ($backups === []) {
			CLI::write('No backups found.', 'yellow');
			return;
		}

		$rows = [];
		foreach ($backups as $backup) {
			$rows[] = [
				$backup['file_name'] ?? '-',
				(string) ($backup['created_at'] ?? '-'),
				number_format((int) ($backup['size_bytes'] ?? 0)),
			];
		}

		CLI::table($rows, ['File Name', 'Created At', 'Size (bytes)']);
	}
}
