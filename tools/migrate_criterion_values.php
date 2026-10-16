<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';
$db = $config['db'];

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['name']),
        $db['user'],
        $db['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $col = $pdo->query("SHOW COLUMNS FROM criteria LIKE 'allowed_values'")->fetch();
    if (!$col) {
        $pdo->exec("ALTER TABLE criteria ADD COLUMN allowed_values VARCHAR(255) NOT NULL DEFAULT '[]' AFTER max_points");
        echo "Column allowed_values added.\n";
    } else {
        echo "Column allowed_values already exists.\n";
    }

    // Для старых критериев без значений: если max_points > 0, предложить одно значение = max
    $rows = $pdo->query('SELECT id, max_points, allowed_values FROM criteria')->fetchAll(PDO::FETCH_ASSOC);
    $upd = $pdo->prepare('UPDATE criteria SET allowed_values = ? WHERE id = ?');
    foreach ($rows as $row) {
        $decoded = json_decode((string) $row['allowed_values'], true);
        if (is_array($decoded) && $decoded !== []) {
            continue;
        }
        $max = (float) $row['max_points'];
        if ($max > 0) {
            $upd->execute([json_encode([$max], JSON_UNESCAPED_UNICODE), $row['id']]);
        }
    }

    echo "Migration OK.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
