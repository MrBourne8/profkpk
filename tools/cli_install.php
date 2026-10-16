<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';
$db = $config['db'];

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;charset=utf8mb4', $db['host']),
        $db['user'],
        $db['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $db['name']) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . str_replace('`', '``', $db['name']) . '`');

    $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
    $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $schema) ?: []));
    foreach ($statements as $statement) {
        if ($statement === '' || str_starts_with($statement, '--')) {
            continue;
        }
        $pdo->exec($statement);
    }

    $hash = password_hash($config['default_admin']['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO users (login, password_hash, display_name, role)
         VALUES (?, ?, ?, 'admin')
         ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'admin'"
    );
    $stmt->execute([$config['default_admin']['login'], $hash, 'Администратор']);

    echo "Install OK. Login: {$config['default_admin']['login']} / {$config['default_admin']['password']}\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Install failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
