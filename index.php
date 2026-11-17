<?php

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

header('X-Profkpk-Build: 2026-11-17-maxpoints');

$route = $_GET['r'] ?? 'home';
$route = preg_replace('/[^a-z0-9_]/', '', (string) $route) ?: 'home';

// Сброс OPcache по запросу: ?opcache_reset=1 (после входа)
if (!empty($_GET['opcache_reset']) && function_exists('opcache_reset')) {
    auth_check();
    opcache_reset();
    header('Content-Type: text/plain; charset=utf-8');
    echo "opcache_reset: ok\n";
    echo "build: 2026-11-17-maxpoints\n";
    echo 'root: ' . __DIR__ . "\n";
    exit;
}
$publicRoutes = ['login'];

if ($route !== 'login') {
    try {
        $pdo->query('SELECT 1 FROM users LIMIT 1');
    } catch (Throwable $e) {
        redirect('install.php');
    }
}

if (!in_array($route, $publicRoutes, true) && $route !== 'logout') {
    // auth_check внутри страниц, кроме login
}

$pageFile = __DIR__ . '/pages/' . $route . '.php';
if (!is_file($pageFile)) {
    http_response_code(404);
    echo 'Страница не найдена.';
    exit;
}

require $pageFile;
