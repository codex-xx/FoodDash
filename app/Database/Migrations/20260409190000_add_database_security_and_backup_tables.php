<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDatabaseSecurityAndBackupTables extends Migration
{
	public function up()
	{
		$this->addSensitiveColumns('users', true);
		$this->addSensitiveColumns('customers', true);
		$this->addSensitiveColumns('drivers', true);

		$this->createAuthTokensTable();
		$this->createLoginActivitiesTable();
		$this->createDeliveryRecordsTable();
		$this->createPaymentTransactionsTable();
		$this->createBackupRunsTable();
	}

	public function down()
	{
		if ($this->db->tableExists('backup_runs')) {
			$this->forge->dropTable('backup_runs', true);
		}

		if ($this->db->tableExists('payment_transactions')) {
			$this->forge->dropTable('payment_transactions', true);
		}

		if ($this->db->tableExists('delivery_records')) {
			$this->forge->dropTable('delivery_records', true);
		}

		if ($this->db->tableExists('login_activities')) {
			$this->forge->dropTable('login_activities', true);
		}

		if ($this->db->tableExists('auth_tokens')) {
			$this->forge->dropTable('auth_tokens', true);
		}

		$this->dropSensitiveColumns('users');
		$this->dropSensitiveColumns('customers');
		$this->dropSensitiveColumns('drivers');
	}

	protected function addSensitiveColumns(string $table, bool $includePhone): void
	{
		if (! $this->db->tableExists($table)) {
			return;
		}

		$columns = [];

		if (! $this->db->fieldExists('email_encrypted', $table)) {
			$columns['email_encrypted'] = [
				'type' => 'TEXT',
				'null' => true,
			];
		}

		if (! $this->db->fieldExists('email_hash', $table)) {
			$columns['email_hash'] = [
				'type' => 'CHAR',
				'constraint' => 64,
				'null' => true,
			];
		}

		if ($includePhone && ! $this->db->fieldExists('phone_encrypted', $table)) {
			$columns['phone_encrypted'] = [
				'type' => 'TEXT',
				'null' => true,
			];
		}

		if ($includePhone && ! $this->db->fieldExists('phone_hash', $table)) {
			$columns['phone_hash'] = [
				'type' => 'CHAR',
				'constraint' => 64,
				'null' => true,
			];
		}

		if (! empty($columns)) {
			$this->forge->addColumn($table, $columns);
		}

		$this->addIndexIfMissing($table, 'idx_' . $table . '_email_hash', 'email_hash');

		if ($includePhone) {
			$this->addIndexIfMissing($table, 'idx_' . $table . '_phone_hash', 'phone_hash');
		}
	}

	protected function dropSensitiveColumns(string $table): void
	{
		if (! $this->db->tableExists($table)) {
			return;
		}

		$dropColumns = [];
		foreach (['email_encrypted', 'email_hash', 'phone_encrypted', 'phone_hash'] as $column) {
			if ($this->db->fieldExists($column, $table)) {
				$dropColumns[] = $column;
			}
		}

		if (! empty($dropColumns)) {
			$this->forge->dropColumn($table, $dropColumns);
		}
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

	protected function createAuthTokensTable(): void
	{
		if ($this->db->tableExists('auth_tokens')) {
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
			'jti' => [
				'type' => 'VARCHAR',
				'constraint' => 64,
			],
			'token_hash' => [
				'type' => 'CHAR',
				'constraint' => 64,
			],
			'issued_at' => [
				'type' => 'DATETIME',
			],
			'expires_at' => [
				'type' => 'DATETIME',
			],
			'revoked_at' => [
				'type' => 'DATETIME',
				'null' => true,
			],
			'ip_address' => [
				'type' => 'VARCHAR',
				'constraint' => 45,
				'null' => true,
			],
			'user_agent' => [
				'type' => 'VARCHAR',
				'constraint' => 255,
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
		$this->forge->addUniqueKey('jti');
		$this->forge->addUniqueKey('token_hash');
		$this->forge->addKey(['user_type', 'user_id']);
		$this->forge->addKey('expires_at');
		$this->forge->addKey('revoked_at');
		$this->forge->createTable('auth_tokens', true);
	}

	protected function createLoginActivitiesTable(): void
	{
		if ($this->db->tableExists('login_activities')) {
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
				'null' => true,
			],
			'user_id' => [
				'type' => 'INT',
				'constraint' => 11,
				'unsigned' => true,
				'null' => true,
			],
			'email_hash' => [
				'type' => 'CHAR',
				'constraint' => 64,
				'null' => true,
			],
			'ip_address' => [
				'type' => 'VARCHAR',
				'constraint' => 45,
				'null' => true,
			],
			'user_agent' => [
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => true,
			],
			'success' => [
				'type' => 'TINYINT',
				'constraint' => 1,
				'default' => 0,
			],
			'failure_reason' => [
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => true,
			],
			'login_at' => [
				'type' => 'DATETIME',
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
		$this->forge->addKey('email_hash');
		$this->forge->addKey('success');
		$this->forge->addKey('login_at');
		$this->forge->createTable('login_activities', true);
	}

	protected function createDeliveryRecordsTable(): void
	{
		if ($this->db->tableExists('delivery_records')) {
			return;
		}

		$this->forge->addField([
			'id' => [
				'type' => 'INT',
				'constraint' => 11,
				'unsigned' => true,
				'auto_increment' => true,
			],
			'order_id' => [
				'type' => 'INT',
				'constraint' => 11,
				'unsigned' => true,
			],
			'driver_id' => [
				'type' => 'INT',
				'constraint' => 11,
				'unsigned' => true,
			],
			'status' => [
				'type' => 'VARCHAR',
				'constraint' => 50,
				'default' => 'assigned',
			],
			'pickup_time' => [
				'type' => 'DATETIME',
				'null' => true,
			],
			'delivered_time' => [
				'type' => 'DATETIME',
				'null' => true,
			],
			'distance_km' => [
				'type' => 'DECIMAL',
				'constraint' => '8,2',
				'null' => true,
			],
			'proof_image' => [
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => true,
			],
			'notes' => [
				'type' => 'TEXT',
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
		$this->forge->addUniqueKey('order_id');
		$this->forge->addKey('driver_id');
		$this->forge->addKey('status');
		$this->forge->createTable('delivery_records', true);
	}

	protected function createPaymentTransactionsTable(): void
	{
		if ($this->db->tableExists('payment_transactions')) {
			return;
		}

		$this->forge->addField([
			'id' => [
				'type' => 'BIGINT',
				'constraint' => 20,
				'unsigned' => true,
				'auto_increment' => true,
			],
			'order_id' => [
				'type' => 'INT',
				'constraint' => 11,
				'unsigned' => true,
			],
			'customer_id' => [
				'type' => 'INT',
				'constraint' => 11,
				'unsigned' => true,
				'null' => true,
			],
			'restaurant_id' => [
				'type' => 'INT',
				'constraint' => 11,
				'unsigned' => true,
				'null' => true,
			],
			'provider' => [
				'type' => 'VARCHAR',
				'constraint' => 50,
				'null' => true,
			],
			'reference' => [
				'type' => 'VARCHAR',
				'constraint' => 120,
				'null' => true,
			],
			'amount' => [
				'type' => 'DECIMAL',
				'constraint' => '10,2',
			],
			'currency' => [
				'type' => 'CHAR',
				'constraint' => 3,
				'default' => 'PHP',
			],
			'status' => [
				'type' => 'VARCHAR',
				'constraint' => 50,
				'default' => 'pending',
			],
			'payment_payload_encrypted' => [
				'type' => 'LONGTEXT',
				'null' => true,
			],
			'payment_payload_hash' => [
				'type' => 'CHAR',
				'constraint' => 64,
				'null' => true,
			],
			'paid_at' => [
				'type' => 'DATETIME',
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
		$this->forge->addKey('order_id');
		$this->forge->addKey('customer_id');
		$this->forge->addKey('restaurant_id');
		$this->forge->addKey('status');
		$this->forge->addKey('paid_at');
		$this->forge->addUniqueKey('reference');
		$this->forge->createTable('payment_transactions', true);
	}

	protected function createBackupRunsTable(): void
	{
		if ($this->db->tableExists('backup_runs')) {
			return;
		}

		$this->forge->addField([
			'id' => [
				'type' => 'BIGINT',
				'constraint' => 20,
				'unsigned' => true,
				'auto_increment' => true,
			],
			'file_name' => [
				'type' => 'VARCHAR',
				'constraint' => 255,
			],
			'file_path' => [
				'type' => 'VARCHAR',
				'constraint' => 500,
			],
			'storage_type' => [
				'type' => 'VARCHAR',
				'constraint' => 30,
				'default' => 'local',
			],
			'backup_size_bytes' => [
				'type' => 'BIGINT',
				'constraint' => 20,
				'unsigned' => true,
				'default' => 0,
			],
			'checksum_sha256' => [
				'type' => 'CHAR',
				'constraint' => 64,
				'null' => true,
			],
			'status' => [
				'type' => 'VARCHAR',
				'constraint' => 20,
				'default' => 'success',
			],
			'started_at' => [
				'type' => 'DATETIME',
				'null' => true,
			],
			'finished_at' => [
				'type' => 'DATETIME',
				'null' => true,
			],
			'initiated_by' => [
				'type' => 'VARCHAR',
				'constraint' => 100,
				'null' => true,
			],
			'notes' => [
				'type' => 'TEXT',
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
		$this->forge->addKey('status');
		$this->forge->addKey('created_at');
		$this->forge->addUniqueKey('checksum_sha256');
		$this->forge->createTable('backup_runs', true);
	}
}
