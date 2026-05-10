<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserLockoutFields extends Migration
{
    /**
     * WARNING: This migration adds account lockout fields used by the
     * Account Lockout & Brute Force Protection feature.
     *
     * IMPORTANT SECURITY NOTES for operators:
     * - These columns store counters and timestamps used to throttle
     *   repeated failed login attempts. Do NOT make these values user-editable
     *   in admin interfaces without careful validation.
     * - Back up your database before running migrations in production.
     * - Test the flow on staging before deploying to live environments.
     */
    public function up()
    {
        // Add the columns if they don't already exist
        $fields = [
            'failed_attempts' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'null' => false,
            ],
            'locked_until' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'lock_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'null' => false,
            ],
        ];

        // Use schema introspection to avoid errors on re-run
        $db = \Config\Database::connect();
        if (! $db->tableExists('users')) {
            // If users table missing, nothing to do here
            return;
        }

        // Only add columns that are missing
        $forge = $this->forge;
        $fieldsToAdd = [];
        foreach ($fields as $name => $def) {
            if (! $db->fieldExists($name, 'users')) {
                $fieldsToAdd[$name] = $def;
            }
        }

        if ($fieldsToAdd !== []) {
            $forge->addColumn('users', $fieldsToAdd);
        }

        // Add index for faster locked_until checks if not present
        if (! $db->fieldExists('locked_until', 'users')) {
            // already handled above
        }

        // raw query to add index if not exists
        try {
            $indexName = 'idx_users_locked_until';
            $hasIndex = false;
            $indexes = $db->getIndexData('users');
            foreach ($indexes as $idx) {
                if (($idx['name'] ?? '') === $indexName) {
                    $hasIndex = true;
                    break;
                }
            }
            if (! $hasIndex) {
                $db->query("ALTER TABLE `users` ADD INDEX `{$indexName}` (`locked_until`)");
            }
        } catch (\Throwable $e) {
            // Non-fatal: if index creation fails, log and continue
            log_message('error', 'Failed to add index idx_users_locked_until: ' . $e->getMessage());
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('users')) {
            return;
        }

        // Drop columns if they exist
        try {
            if ($db->fieldExists('failed_attempts', 'users')) {
                $this->forge->dropColumn('users', 'failed_attempts');
            }
            if ($db->fieldExists('locked_until', 'users')) {
                $this->forge->dropColumn('users', 'locked_until');
            }
            if ($db->fieldExists('lock_count', 'users')) {
                $this->forge->dropColumn('users', 'lock_count');
            }

            // Drop index if present
            $db->query('ALTER TABLE `users` DROP INDEX IF EXISTS `idx_users_locked_until`');
        } catch (\Throwable $e) {
            log_message('error', 'Failed to rollback AddUserLockoutFields migration: ' . $e->getMessage());
        }
    }
}
