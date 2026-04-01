<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSyncFieldsToMenuItems extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('image_url', 'menu_items')) {
            $fields['image_url'] = [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'image',
            ];
        }

        if (! $this->db->fieldExists('availability', 'menu_items')) {
            $fields['availability'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'after' => 'is_available',
            ];
        }

        if (! empty($fields)) {
            $this->forge->addColumn('menu_items', $fields);
        }

        if ($this->db->fieldExists('image', 'menu_items') && $this->db->fieldExists('image_url', 'menu_items')) {
            $this->db->query("UPDATE menu_items SET image_url = image WHERE image_url IS NULL AND image IS NOT NULL");
        }

        if ($this->db->fieldExists('is_available', 'menu_items') && $this->db->fieldExists('availability', 'menu_items')) {
            $this->db->query("UPDATE menu_items SET availability = is_available");
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('image_url', 'menu_items')) {
            $this->forge->dropColumn('menu_items', 'image_url');
        }

        if ($this->db->fieldExists('availability', 'menu_items')) {
            $this->forge->dropColumn('menu_items', 'availability');
        }
    }
}
