<?php

namespace App\Commands;

use App\Libraries\DatabaseBackupService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DatabaseBackupCommand extends BaseCommand
{
	protected $group = 'Database';
	protected $name = 'db:backup';
	protected $description = 'Create a SQL backup of the FoodDash database.';
	protected $usage = 'db:backup [--label=LABEL] [--by=USER]';
	protected $options = [
		'--label' => 'Label to include in the backup filename.',
		'--by' => 'Person or process that initiated the backup.',
	];

	public function run(array $params)
	{
		$label = CLI::getOption('label') ?: 'manual';
		$initiatedBy = CLI::getOption('by') ?: 'cli';

		$service = new DatabaseBackupService();
		$result = $service->createBackup($label, $initiatedBy);

		if (! ($result['success'] ?? false)) {
			CLI::error('Backup failed: ' . ($result['message'] ?? 'Unknown error'));
			return;
		}

		CLI::write('Backup completed successfully.', 'green');
		CLI::write('File: ' . ($result['file_name'] ?? 'n/a'));
		CLI::write('Path: ' . ($result['file_path'] ?? 'n/a'));
		CLI::write('Size: ' . number_format((int) ($result['size_bytes'] ?? 0)) . ' bytes');
	}
}
