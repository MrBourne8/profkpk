<?php

declare(strict_types=1);

function db_connect(array $db): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $db['host'],
        $db['name'],
        $db['charset']
    );

    try {
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        // Для install — подключение без выбора БД
        if ($script === 'install.php' || $script === 'cli_install.php') {
            $dsnNoDb = sprintf('mysql:host=%s;charset=%s', $db['host'], $db['charset']);
            return new PDO($dsnNoDb, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        // Неизвестная БД / нет таблиц — сразу на установщик
        $code = (int) ($e->errorInfo[1] ?? 0);
        if ($code === 1049 || str_contains($e->getMessage(), 'Unknown database')) {
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
            header('Location: ' . ($base === '' ? '' : $base) . '/install.php');
            exit;
        }

        $installHref = 'install.php';
        $msg = 'Не удалось подключиться к БД. Откройте <a href="' . htmlspecialchars($installHref) . '">install.php</a> для установки.';
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><title>Ошибка БД</title></head><body style="font-family:sans-serif;padding:2rem">';
        echo '<p>' . $msg . '</p><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>';
        exit;
    }

    return $pdo;
}

function db_fetch(PDO $pdo, string $sql, array $params = []): ?array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function db_fetch_all(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_exec(PDO $pdo, string $sql, array $params = []): bool
{
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function db_last_id(PDO $pdo): string
{
    return $pdo->lastInsertId();
}
