<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeletedAtToMenus extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('menus') || $this->db->fieldExists('deleted_at', 'menus')) {
            return;
        }

        $this->forge->addColumn('menus', [
            'deleted_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'after' => 'updated_at',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->tableExists('menus') && $this->db->fieldExists('deleted_at', 'menus')) {
            $this->forge->dropColumn('menus', 'deleted_at');
        }
    }
}
