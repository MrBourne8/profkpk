<?php

declare(strict_types=1);

$config = require __DIR__ . '/config/config.php';

$messages = [];
$errors = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? $config['db']['host']);
    $name = trim($_POST['name'] ?? $config['db']['name']);
    $user = trim($_POST['user'] ?? $config['db']['user']);
    $pass = (string) ($_POST['pass'] ?? $config['db']['pass']);
    $adminLogin = trim($_POST['admin_login'] ?? 'admin');
    $adminPassword = (string) ($_POST['admin_password'] ?? 'admin123');

    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;charset=utf8mb4', $host),
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $name) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . str_replace('`', '``', $name) . '`');

        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('Не найден database/schema.sql');
        }
        $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $schema) ?: []));
        foreach ($statements as $statement) {
            if ($statement === '' || str_starts_with($statement, '--')) {
                continue;
            }
            $pdo->exec($statement);
        }

        $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (login, password_hash, display_name, role)
             VALUES (?, ?, ?, 'admin')
             ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), display_name = VALUES(display_name), role = 'admin'"
        );
        $stmt->execute([$adminLogin, $hash, 'Администратор']);

        // Обновить config.php с введёнными данными
        $configPath = __DIR__ . '/config/config.php';
        $configContent = file_get_contents($configPath);
        if ($configContent !== false) {
            $configContent = preg_replace(
                "/'host'\\s*=>\\s*'[^']*'/",
                "'host' => '" . addslashes($host) . "'",
                $configContent,
                1
            );
            $configContent = preg_replace(
                "/'name'\\s*=>\\s*'[^']*'/",
                "'name' => '" . addslashes($name) . "'",
                $configContent,
                1
            );
            $configContent = preg_replace(
                "/'user'\\s*=>\\s*'[^']*'/",
                "'user' => '" . addslashes($user) . "'",
                $configContent,
                1
            );
            $configContent = preg_replace(
                "/'pass'\\s*=>\\s*'[^']*'/",
                "'pass' => '" . addslashes($pass) . "'",
                $configContent,
                1
            );
            file_put_contents($configPath, $configContent);
        }

        $messages[] = 'База данных создана, таблицы установлены, пользователь администратора готов.';
        $done = true;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Установка — Профессионалы</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <h1>Установка системы</h1>
    <p class="muted">Создание БД MySQL и учётной записи секретаря конкурса.</p>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>
    <?php foreach ($messages as $msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endforeach; ?>

    <?php if ($done): ?>
        <p><a class="btn btn-primary" href="index.php">Перейти к входу</a></p>
    <?php else: ?>
        <form method="post" class="form">
            <label>Хост MySQL
                <input type="text" name="host" value="<?= htmlspecialchars($config['db']['host']) ?>" required>
            </label>
            <label>Имя базы
                <input type="text" name="name" value="<?= htmlspecialchars($config['db']['name']) ?>" required>
            </label>
            <label>Пользователь MySQL
                <input type="text" name="user" value="<?= htmlspecialchars($config['db']['user']) ?>" required>
            </label>
            <label>Пароль MySQL
                <input type="password" name="pass" value="<?= htmlspecialchars($config['db']['pass']) ?>">
            </label>
            <hr>
            <label>Логин администратора
                <input type="text" name="admin_login" value="admin" required>
            </label>
            <label>Пароль администратора
                <input type="text" name="admin_password" value="admin123" required>
            </label>
            <button type="submit" class="btn btn-primary">Установить</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
