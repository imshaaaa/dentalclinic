<?php

function db(): ?PDO
{
    static $pdo = false;

    if ($pdo !== false) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $db = $config['database'];
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['name'],
        $db['charset']
    );

    try {
        $pdo = new PDO($dsn, $db['username'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $exception) {
        $pdo = null;
    }

    return $pdo;
}
