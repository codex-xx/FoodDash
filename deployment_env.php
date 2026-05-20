<?php

declare(strict_types=1);

/**
 * Read environment values from either the process environment or Render-style
 * uppercase variables, with a fallback to the provided default.
 */
function fooddash_env(string $key, mixed $default = null): mixed
{
    $candidates = [
        $key,
        strtoupper(str_replace(['.', '-', ' '], '_', $key)),
    ];

    foreach ($candidates as $candidate) {
        $value = getenv($candidate);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }

    return $default;
}

function fooddash_db_config(): array
{
    $databaseUrl = (string) fooddash_env('DATABASE_URL', fooddash_env('MYSQL_URL', ''));
    if ($databaseUrl !== '') {
        $parsed = fooddash_parse_database_url($databaseUrl);
        if ($parsed !== null) {
            return $parsed;
        }
    }

    return [
        'hostname' => (string) fooddash_env('DB_HOST', 'localhost'),
        'username' => (string) fooddash_env('DB_USER', 'root'),
        'password' => (string) fooddash_env('DB_PASSWORD', ''),
        'database' => (string) fooddash_env('DB_NAME', 'fooddash_db'),
        'port' => (int) fooddash_env('DB_PORT', 3306),
    ];
}

function fooddash_db_connection(): mysqli
{
    $config = fooddash_db_config();

    $conn = new mysqli(
        $config['hostname'],
        $config['username'],
        $config['password'],
        $config['database'],
        (int) $config['port'],
    );

    if ($conn->connect_errno) {
        throw new RuntimeException('Database connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}

function fooddash_parse_database_url(string $databaseUrl): ?array
{
    $parts = parse_url($databaseUrl);
    if ($parts === false || ! isset($parts['host'])) {
        return null;
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? 'mysql'));
    if (! in_array($scheme, ['mysql', 'mariadb'], true)) {
        return null;
    }

    return [
        'hostname' => (string) $parts['host'],
        'username' => isset($parts['user']) ? rawurldecode((string) $parts['user']) : 'root',
        'password' => isset($parts['pass']) ? rawurldecode((string) $parts['pass']) : '',
        'database' => isset($parts['path']) && ltrim((string) $parts['path'], '/') !== ''
            ? rawurldecode(ltrim((string) $parts['path'], '/'))
            : 'fooddash_db',
        'port' => isset($parts['port']) ? (int) $parts['port'] : 3306,
    ];
}