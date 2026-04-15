<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIntrusionDetectionTables extends Migration
{
    public function up()
    {
        $this->createAuditLogsTable();
        $this->createIntrusionAlertsTable();
        $this->createBlockedIpsTable();
    }

    public function down()
    {
        if ($this->db->tableExists('blocked_ips')) {
            $this->forge->dropTable('blocked_ips', true);
        }

        if ($this->db->tableExists('intrusion_alerts')) {
            $this->forge->dropTable('intrusion_alerts', true);
        }

        if ($this->db->tableExists('audit_logs')) {
            $this->forge->dropTable('audit_logs', true);
        }
    }

    protected function createAuditLogsTable(): void
    {
        if ($this->db->tableExists('audit_logs')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'ip_address_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'device_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'event_description' => [
                'type' => 'TEXT',
            ],
            'severity' => [
                'type' => 'ENUM',
                'constraint' => ['low', 'medium', 'high', 'critical'],
                'default' => 'medium',
            ],
            'event_meta_encrypted' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'event_meta_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'prev_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'record_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
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
        $this->forge->addKey('user_id');
        $this->forge->addKey('ip_address_hash');
        $this->forge->addKey('device_hash');
        $this->forge->addKey('event_type');
        $this->forge->addKey('severity');
        $this->forge->addKey('event_meta_hash');
        $this->forge->addKey('prev_hash');
        $this->forge->addKey('record_hash');
        $this->forge->addKey('created_at');
        $this->forge->createTable('audit_logs', true);
    }

    protected function createIntrusionAlertsTable(): void
    {
        if ($this->db->tableExists('intrusion_alerts')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'audit_log_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'ip_address_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'alert_type' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['open', 'acknowledged', 'resolved'],
                'default' => 'open',
            ],
            'severity' => [
                'type' => 'ENUM',
                'constraint' => ['medium', 'high', 'critical'],
                'default' => 'high',
            ],
            'alert_message' => [
                'type' => 'TEXT',
            ],
            'trigger_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 1,
            ],
            'triggered_at' => [
                'type' => 'DATETIME',
            ],
            'resolved_at' => [
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
        $this->forge->addKey('audit_log_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('ip_address_hash');
        $this->forge->addKey('alert_type');
        $this->forge->addKey('status');
        $this->forge->addKey('severity');
        $this->forge->addKey('triggered_at');
        $this->forge->createTable('intrusion_alerts', true);

        $this->db->query(
            'ALTER TABLE `intrusion_alerts` ADD CONSTRAINT `fk_intrusion_alerts_audit` '
            . 'FOREIGN KEY (`audit_log_id`) REFERENCES `audit_logs`(`id`) ON DELETE SET NULL'
        );
    }

    protected function createBlockedIpsTable(): void
    {
        if ($this->db->tableExists('blocked_ips')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'ip_address_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
            ],
            'reason' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'blocked_at' => [
                'type' => 'DATETIME',
            ],
            'blocked_until' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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
        $this->forge->addKey('ip_address_hash');
        $this->forge->addKey('is_active');
        $this->forge->addKey('blocked_until');
        $this->forge->addKey('blocked_at');
        $this->forge->createTable('blocked_ips', true);
    }
}
