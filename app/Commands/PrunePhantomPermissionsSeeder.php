<?php

namespace App\Commands;

use App\Database\Seeds\PrunePhantomPermissionsSeeder as PrunePhantomPermissionsSeederClass;
use CodeIgniter\CLI\BaseCommand;

class PrunePhantomPermissionsSeeder extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'PrunePhantomPermissionsSeeder';
    protected $description = 'Prune phantom permissions and resync built-in system roles.';
    protected $usage = 'PrunePhantomPermissionsSeeder';

    public function run(array $params)
    {
        (new PrunePhantomPermissionsSeederClass(config('Database')))->run();
    }
}
