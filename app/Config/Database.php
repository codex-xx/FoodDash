<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     *
     * @var array<string, mixed>
     */
    public array $default = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => 'root',
        'password'     => '',
        'database'     => 'fooddash_db',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
        'foundRows'    => false,
        'dateFormat'   => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    //    /**
    //     * Sample database connection for SQLite3.
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'database'    => 'database.db',
    //        'DBDriver'    => 'SQLite3',
    //        'DBPrefix'    => '',
    //        'DBDebug'     => true,
    //        'swapPre'     => '',
    //        'failover'    => [],
    //        'foreignKeys' => true,
    //        'busyTimeout' => 1000,
    //        'synchronous' => null,
    //        'dateFormat'  => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    //    /**
    //     * Sample database connection for Postgre.
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'DSN'        => '',
    //        'hostname'   => 'localhost',
    //        'username'   => 'root',
    //        'password'   => 'root',
    //        'database'   => 'ci4',
    //        'schema'     => 'public',
    //        'DBDriver'   => 'Postgre',
    //        'DBPrefix'   => '',
    //        'pConnect'   => false,
    //        'DBDebug'    => true,
    //        'charset'    => 'utf8',
    //        'swapPre'    => '',
    //        'failover'   => [],
    //        'port'       => 5432,
    //        'dateFormat' => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    //    /**
    //     * Sample database connection for SQLSRV.
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'DSN'        => '',
    //        'hostname'   => 'localhost',
    //        'username'   => 'root',
    //        'password'   => 'root',
    //        'database'   => 'ci4',
    //        'schema'     => 'dbo',
    //        'DBDriver'   => 'SQLSRV',
    //        'DBPrefix'   => '',
    //        'pConnect'   => false,
    //        'DBDebug'    => true,
    //        'charset'    => 'utf8',
    //        'swapPre'    => '',
    //        'encrypt'    => false,
    //        'failover'   => [],
    //        'port'       => 1433,
    //        'dateFormat' => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    //    /**
    //     * Sample database connection for OCI8.
    //     *
    //     * You may need the following environment variables:
    //     *   NLS_LANG                = 'AMERICAN_AMERICA.UTF8'
    //     *   NLS_DATE_FORMAT         = 'YYYY-MM-DD HH24:MI:SS'
    //     *   NLS_TIMESTAMP_FORMAT    = 'YYYY-MM-DD HH24:MI:SS'
    //     *   NLS_TIMESTAMP_TZ_FORMAT = 'YYYY-MM-DD HH24:MI:SS'
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'DSN'        => 'localhost:1521/XEPDB1',
    //        'username'   => 'root',
    //        'password'   => 'root',
    //        'DBDriver'   => 'OCI8',
    //        'DBPrefix'   => '',
    //        'pConnect'   => false,
    //        'DBDebug'    => true,
    //        'charset'    => 'AL32UTF8',
    //        'swapPre'    => '',
    //        'failover'   => [],
    //        'dateFormat' => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    /**
     * This database connection is used when running PHPUnit database tests.
     *
     * @var array<string, mixed>
     */
    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => '',
        'password'    => '',
        'database'    => ':memory:',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8',
        'DBCollat'    => '',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
        'synchronous' => null,
        'dateFormat'  => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $databaseUrl = $this->firstEnvValue(['DATABASE_URL', 'MYSQL_URL']);
        if ($databaseUrl !== null) {
            $this->applyDatabaseUrl($databaseUrl);
        }

        $this->default['DSN'] = $this->firstEnvValue(['database.default.DSN', 'DB_DSN']) ?? $this->default['DSN'];
        $this->default['hostname'] = $this->firstEnvValue(['database.default.hostname', 'DB_HOST']) ?? $this->default['hostname'];
        $this->default['username'] = $this->firstEnvValue(['database.default.username', 'DB_USER']) ?? $this->default['username'];
        $this->default['password'] = $this->firstEnvValue(['database.default.password', 'DB_PASSWORD']) ?? $this->default['password'];
        $this->default['database'] = $this->firstEnvValue(['database.default.database', 'DB_NAME']) ?? $this->default['database'];
        $this->default['DBDriver'] = $this->firstEnvValue(['database.default.DBDriver', 'DB_DRIVER']) ?? $this->default['DBDriver'];
        $this->default['DBPrefix'] = $this->firstEnvValue(['database.default.DBPrefix', 'DB_PREFIX']) ?? $this->default['DBPrefix'];
        $this->default['pConnect'] = $this->firstBoolEnvValue(['database.default.pConnect', 'DB_PCONNECT'], $this->default['pConnect']);
        $this->default['DBDebug'] = $this->firstBoolEnvValue(['database.default.DBDebug', 'DB_DEBUG'], ENVIRONMENT !== 'production');
        $this->default['charset'] = $this->firstEnvValue(['database.default.charset', 'DB_CHARSET']) ?? $this->default['charset'];
        $this->default['DBCollat'] = $this->firstEnvValue(['database.default.DBCollat', 'DB_COLLATE']) ?? $this->default['DBCollat'];
        $this->default['swapPre'] = $this->firstEnvValue(['database.default.swapPre', 'DB_SWAP_PREFIX']) ?? $this->default['swapPre'];
        $this->default['encrypt'] = $this->firstBoolEnvValue(['database.default.encrypt', 'DB_ENCRYPT'], $this->default['encrypt']);
        $this->default['compress'] = $this->firstBoolEnvValue(['database.default.compress', 'DB_COMPRESS'], $this->default['compress']);
        $this->default['strictOn'] = $this->firstBoolEnvValue(['database.default.strictOn', 'DB_STRICT_ON'], $this->default['strictOn']);
        $this->default['port'] = $this->firstIntEnvValue(['database.default.port', 'DB_PORT']) ?? $this->default['port'];
        $this->default['numberNative'] = $this->firstBoolEnvValue(['database.default.numberNative', 'DB_NUMBER_NATIVE'], $this->default['numberNative']);
        $this->default['foundRows'] = $this->firstBoolEnvValue(['database.default.foundRows', 'DB_FOUND_ROWS'], $this->default['foundRows']);

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }
    }

    /**
     * @param list<string> $keys
     */
    private function firstEnvValue(array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = env($key);
            if (is_string($value) && $value !== '') {
                return $value;
            }

            $serverValue = getenv($key);
            if ($serverValue !== false && $serverValue !== '') {
                return $serverValue;
            }
        }

        return null;
    }

    /**
     * @param list<string> $keys
     */
    private function firstIntEnvValue(array $keys): ?int
    {
        $value = $this->firstEnvValue($keys);

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param list<string> $keys
     */
    private function firstBoolEnvValue(array $keys, bool $default): bool
    {
        foreach ($keys as $key) {
            $value = env($key);
            if ($value === null || $value === '') {
                $value = getenv($key);
            }

            if ($value === false || $value === null || $value === '') {
                continue;
            }

            if (is_bool($value)) {
                return $value;
            }

            $normalized = strtolower((string) $value);
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return $default;
    }

    private function applyDatabaseUrl(string $databaseUrl): void
    {
        $parts = parse_url($databaseUrl);
        if ($parts === false || ! isset($parts['host'])) {
            return;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'mysql'));
        if (! in_array($scheme, ['mysql', 'mariadb'], true)) {
            return;
        }

        $this->default['hostname'] = (string) $parts['host'];
        $this->default['username'] = (string) ($parts['user'] ?? $this->default['username']);
        $this->default['password'] = (string) ($parts['pass'] ?? $this->default['password']);
        $this->default['database'] = isset($parts['path']) ? ltrim((string) $parts['path'], '/') : $this->default['database'];
        $this->default['port'] = isset($parts['port']) ? (int) $parts['port'] : $this->default['port'];
    }
}
