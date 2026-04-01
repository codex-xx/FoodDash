<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMenusTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('menus')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'restaurant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'image_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'availability' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('restaurant_id');
        $this->forge->createTable('menus', true);

        if ($this->db->tableExists('menu_items')) {
            $this->db->query(
                'INSERT INTO menus (restaurant_id, name, description, price, image_url, category, availability, created_at, updated_at) '
                . 'SELECT restaurant_id, name, description, price, COALESCE(image_url, image) AS image_url, category, COALESCE(availability, is_available, 1) AS availability, created_at, updated_at '
                . 'FROM menu_items'
            );
        }
    }

    public function down()
    {
        $this->forge->dropTable('menus', true);
    }
}
