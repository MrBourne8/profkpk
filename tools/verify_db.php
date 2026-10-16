<?php

declare(strict_types=1);

require dirname(__DIR__) . '/lib/bootstrap.php';

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo 'Tables: ' . implode(', ', $tables) . PHP_EOL;
$user = $pdo->query('SELECT login FROM users LIMIT 1')->fetch();
echo 'Admin: ' . ($user['login'] ?? 'none') . PHP_EOL;
