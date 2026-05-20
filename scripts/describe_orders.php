<?php

require_once dirname(__DIR__) . '/deployment_env.php';

$m = fooddash_db_connection();

$res = $m->query('DESCRIBE orders');
if (! $res) {
    echo "DESCRIBE failed: " . $m->error . "\n";
    exit(1);
}
while ($r = $res->fetch_assoc()) {
    print_r($r);
}
