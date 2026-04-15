<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSessionActivitySecurityLogging extends Migration
{
	public function up()
	{
		$this->alterAuthTokensTable();
		$this->alterLoginActivitiesTable();
		$this->createUserActivityLogsTable();
	}

	public function down()
	{
		if ($this->db->tableExists('user_activity_logs')) {
			$this->forge->dropTable('user_activity_logs', true);
		}

		if ($this->db->tableExists('login_activities')) {
			foreach (['event_meta_encrypted', 'event_meta_hash'] as $column) {
				if ($this->db->fieldExists($column, 'login_activities')) {
					$this->forge->dropColumn('login_activities', $column);
				}
			}
		}

		if ($this->db->tableExists('auth_tokens') && $this->db->fieldExists('last_seen_at', 'auth_tokens')) {
			$this->forge->dropColumn('auth_tokens', 'last_seen_at');
		}
	}

	protected function alterAuthTokensTable(): void
	{
		if (! $this->db->tableExists('auth_tokens')) {
			return;
		}

		if (! $this->db->fieldExists('last_seen_at', 'auth_tokens')) {
			$this->forge->addColumn('auth_tokens', [
				'last_seen_at' => [
					'type' => 'DATETIME',
					'null' => true,
				],
			]);
		}

		$this->addIndexIfMissing('auth_tokens', 'idx_auth_tokens_last_seen_at', 'last_seen_at');
	}

	protected function alterLoginActivitiesTable(): void
	{
		if (! $this->db->tableExists('login_activities')) {
			return;
		}

		$columns = [];

		if (! $this->db->fieldExists('event_meta_encrypted', 'login_activities')) {
			$columns['event_meta_encrypted'] = [
				'type' => 'LONGTEXT',
				'null' => true,
			];
		}

		if (! $this->db->fieldExists('event_meta_hash', 'login_activities')) {
			$columns['event_meta_hash'] = [
				'type' => 'CHAR',
				'constraint' => 64,
				'null' => true,
			];
		}

		if (! empty($columns)) {
			$this->forge->addColumn('login_activities', $columns);
		}

		$this->addIndexIfMissing('login_activities', 'idx_login_activities_event_meta_hash', 'event_meta_hash');
	}

	protected function createUserActivityLogsTable(): void
	{
		if ($this->db->tableExists('user_activity_logs')) {
			return;
		}

		$this->forge->addField([
			'id' => [
				'type' => 'BIGINT',
				'constraint' => 20,
				'unsigned' => true,
				'auto_increment' => true,
			],
			'user_type' => [
				'type' => 'VARCHAR',
				'constraint' => 20,
			],
			'user_id' => [
				'type' => 'INT',
				'constraint' => 11,
				'unsigned' => true,
			],
			'activity_type' => [
				'type' => 'VARCHAR',
				'constraint' => 80,
			],
			'target_type' => [
				'type' => 'VARCHAR',
				'constraint' => 80,
				'null' => true,
			],
			'target_id' => [
				'type' => 'INT',
				'constraint' => 11,
				'unsigned' => true,
				'null' => true,
			],
			'activity_meta_encrypted' => [
				'type' => 'LONGTEXT',
				'null' => true,
			],
			'activity_meta_hash' => [
				'type' => 'CHAR',
				'constraint' => 64,
				'null' => true,
			],
			'ip_hash' => [
				'type' => 'CHAR',
				'constraint' => 64,
				'null' => true,
			],
			'user_agent_hash' => [
				'type' => 'CHAR',
				'constraint' => 64,
				'null' => true,
			],
			'created_at' => [
				'type' => 'DATETIME',
				'null' => true,
			],
			'updated_at' => [
				'type' => 'DATETIME',
				'null' => true,
			],
		]);

		$this->forge->addKey('id', true);
		$this->forge->addKey(['user_type', 'user_id']);
		$this->forge->addKey('activity_type');
		$this->forge->addKey('activity_meta_hash');
		$this->forge->addKey('created_at');
		$this->forge->createTable('user_activity_logs', true);
	}

	protected function addIndexIfMissing(string $table, string $indexName, string $column): void
	{
		if (! $this->db->tableExists($table) || ! $this->db->fieldExists($column, $table)) {
			return;
		}

		if ($this->hasIndex($table, $indexName)) {
			return;
		}

		$this->db->query(sprintf(
			'CREATE INDEX `%s` ON `%s` (`%s`)',
			$indexName,
			$table,
			$column
		));
	}

	protected function hasIndex(string $table, string $indexName): bool
	{
		$query = $this->db->query(sprintf('SHOW INDEX FROM `%s` WHERE Key_name = ?', $table), [$indexName]);
		return $query->getNumRows() > 0;
	}
}
