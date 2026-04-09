<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveDriverVehicleAndLocationColumns extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('drivers')) {
            return;
        }

        $dropColumns = [];

        if ($this->db->fieldExists('vehicle_number', 'drivers')) {
            $dropColumns[] = 'vehicle_number';
        }

        if ($this->db->fieldExists('current_latitude', 'drivers')) {
            $dropColumns[] = 'current_latitude';
        }

        if ($this->db->fieldExists('current_longitude', 'drivers')) {
            $dropColumns[] = 'current_longitude';
        }

        if (! empty($dropColumns)) {
            $this->forge->dropColumn('drivers', $dropColumns);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('drivers')) {
            return;
        }

        $addColumns = [];

        if (! $this->db->fieldExists('vehicle_number', 'drivers')) {
            $addColumns['vehicle_number'] = [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ];
        }

        if (! $this->db->fieldExists('current_latitude', 'drivers')) {
            $addColumns['current_latitude'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,8',
                'null'       => true,
            ];
        }

        if (! $this->db->fieldExists('current_longitude', 'drivers')) {
            $addColumns['current_longitude'] = [
                'type'       => 'DECIMAL',
                'constraint' => '11,8',
                'null'       => true,
            ];
        }

        if (! empty($addColumns)) {
            $this->forge->addColumn('drivers', $addColumns);
        }
    }
}
