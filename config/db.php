<?php
declare(strict_types=1);

/**
 * Secure PDO connection - utf8mb4, exceptions, no emulate prepares.
 * Fill these in with your cPanel/database credentials.
 */
function bb_db_config(): array
{
    return [
        'host'    => getenv('BB_DB_HOST') ?: 'localhost',
        'name'    => getenv('BB_DB_NAME') ?: 'bloodbank_saas',
        'user'    => getenv('BB_DB_USER') ?: 'root',
        'pass'    => getenv('BB_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ];
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = bb_db_config();
    $dsn = "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}";
    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('DB connection failed: ' . $e->getMessage());
        exit('Database connection error. Please try again later.');
    }
    return $pdo;
}
