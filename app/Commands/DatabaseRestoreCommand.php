<?php

namespace App\Commands;

use App\Libraries\DatabaseBackupService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DatabaseRestoreCommand extends BaseCommand
{
	protected $group = 'Database';
	protected $name = 'db:restore';
	protected $description = 'Restore the database from a SQL backup file.';
	protected $usage = 'db:restore --file=FILENAME [--by=USER]';
	protected $options = [
		'--file' => 'Backup filename inside writable/db_backups.',
		'--by' => 'Person or process that initiated the restore.',
	];

	public function run(array $params)
	{
		$file = CLI::getOption('file');
		if (! is_string($file) || trim($file) === '') {
			CLI::error('Please provide a backup file with --file=FILENAME');
			return;
		}

		$initiatedBy = CLI::getOption('by') ?: 'cli';

		$service = new DatabaseBackupService();
		$result = $service->restoreBackup($file, $initiatedBy);

		if (! ($result['success'] ?? false)) {
			CLI::error('Restore failed: ' . ($result['message'] ?? 'Unknown error'));
			return;
		}

		CLI::write('Restore completed successfully.', 'green');
		CLI::write('File: ' . ($result['file_name'] ?? $file));
		CLI::write('Restored At: ' . ($result['restored_at'] ?? date('Y-m-d H:i:s')));
	}
}
