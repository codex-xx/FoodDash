<?php

namespace App\Models;

use CodeIgniter\Model;

class AppSettingModel extends Model
{
    protected $table = 'app_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $allowedFields = [
        'setting_key',
        'setting_value',
    ];

    public function getValue(string $key, mixed $default = null): mixed
    {
        if (! $this->db->tableExists($this->table)) {
            return $default;
        }

        $row = $this->where('setting_key', $key)->first();
        if (! is_array($row)) {
            return $default;
        }

        return $row['setting_value'] ?? $default;
    }

    public function isEnabled(string $key, bool $default = false): bool
    {
        $value = strtolower(trim((string) $this->getValue($key, $default ? '1' : '0')));

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    public function setValue(string $key, mixed $value): bool
    {
        if (! $this->db->tableExists($this->table)) {
            return false;
        }

        $payload = [
            'setting_key' => $key,
            'setting_value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
        ];

        $existing = $this->where('setting_key', $key)->first();

        if (is_array($existing)) {
            return (bool) $this->update((int) $existing['id'], $payload);
        }

        return (bool) $this->insert($payload);
    }
}