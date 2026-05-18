<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomerDriverLocationFields extends Migration
{
    public function up()
    {
        $customerFields = [];
        if (! $this->db->fieldExists('latitude', 'customers')) {
            $customerFields['latitude'] = [
                'type' => 'DOUBLE',
                'null' => true,
                'after' => 'address',
            ];
        }

        if (! $this->db->fieldExists('longitude', 'customers')) {
            $customerFields['longitude'] = [
                'type' => 'DOUBLE',
                'null' => true,
                'after' => 'latitude',
            ];
        }

        if ($customerFields !== []) {
            $this->forge->addColumn('customers', $customerFields);
        }

        $driverFields = [];
        if (! $this->db->fieldExists('address', 'drivers')) {
            $driverFields['address'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'license_number',
            ];
        }

        if (! $this->db->fieldExists('latitude', 'drivers')) {
            $driverFields['latitude'] = [
                'type' => 'DOUBLE',
                'null' => true,
                'after' => 'address',
            ];
        }

        if (! $this->db->fieldExists('longitude', 'drivers')) {
            $driverFields['longitude'] = [
                'type' => 'DOUBLE',
                'null' => true,
                'after' => 'latitude',
            ];
        }

        if ($driverFields !== []) {
            $this->forge->addColumn('drivers', $driverFields);
        }

        if ($this->db->fieldExists('current_latitude', 'drivers') && $this->db->fieldExists('latitude', 'drivers')) {
            $this->db->query('UPDATE drivers SET latitude = current_latitude WHERE latitude IS NULL AND current_latitude IS NOT NULL');
        }

        if ($this->db->fieldExists('current_longitude', 'drivers') && $this->db->fieldExists('longitude', 'drivers')) {
            $this->db->query('UPDATE drivers SET longitude = current_longitude WHERE longitude IS NULL AND current_longitude IS NOT NULL');
        }
    }

    public function down()
    {
        $customerDrop = [];
        if ($this->db->fieldExists('longitude', 'customers')) {
            $customerDrop[] = 'longitude';
        }
        if ($this->db->fieldExists('latitude', 'customers')) {
            $customerDrop[] = 'latitude';
        }

        if ($customerDrop !== []) {
            $this->forge->dropColumn('customers', $customerDrop);
        }

        $driverDrop = [];
        if ($this->db->fieldExists('longitude', 'drivers')) {
            $driverDrop[] = 'longitude';
        }
        if ($this->db->fieldExists('latitude', 'drivers')) {
            $driverDrop[] = 'latitude';
        }
        if ($this->db->fieldExists('address', 'drivers')) {
            $driverDrop[] = 'address';
        }

        if ($driverDrop !== []) {
            $this->forge->dropColumn('drivers', $driverDrop);
        }
    }
}