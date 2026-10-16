<?php

declare(strict_types=1);

/**
 * Применяет миграцию к существующей БД: одна согласованная оценка на критерий.
 */
$config = require dirname(__DIR__) . '/config/config.php';
$db = $config['db'];

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['name']),
        $db['user'],
        $db['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $sql = file_get_contents(dirname(__DIR__) . '/database/migrate_consensus_scores.sql');
    if ($sql === false) {
        throw new RuntimeException('Не найден migrate_consensus_scores.sql');
    }

    $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql) ?: []));
    foreach ($statements as $statement) {
        if ($statement === '' || str_starts_with($statement, '--')) {
            continue;
        }
        $pdo->exec($statement);
    }

    // Убрать устаревшую колонку, если есть
    $col = $pdo->query("SHOW COLUMNS FROM specialties LIKE 'score_method'")->fetch();
    if ($col) {
        $pdo->exec('ALTER TABLE specialties DROP COLUMN score_method');
    }

    echo "Migration OK: scores = consensus (contestant × criterion)\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
