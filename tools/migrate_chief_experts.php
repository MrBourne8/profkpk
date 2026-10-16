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

    $roleCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
    if (!$roleCol) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin','chief_expert') NOT NULL DEFAULT 'admin' AFTER display_name");
        echo "users.role added.\n";
    }

    $pdo->exec("UPDATE users SET role = 'admin' WHERE role = '' OR role IS NULL OR login = 'admin'");

    $ceCol = $pdo->query("SHOW COLUMNS FROM specialties LIKE 'chief_expert_id'")->fetch();
    if (!$ceCol) {
        $pdo->exec('ALTER TABLE specialties ADD COLUMN chief_expert_id INT UNSIGNED NULL AFTER experts_count');
        try {
            $pdo->exec(
                'ALTER TABLE specialties
                 ADD CONSTRAINT fk_specialties_chief_expert
                 FOREIGN KEY (chief_expert_id) REFERENCES users(id) ON DELETE SET NULL'
            );
        } catch (Throwable $e) {
            echo "FK note: " . $e->getMessage() . "\n";
        }
        echo "specialties.chief_expert_id added.\n";
    }

    echo "Migration OK.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
