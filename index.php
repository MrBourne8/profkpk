<?php

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

$route = $_GET['r'] ?? 'home';
$route = preg_replace('/[^a-z0-9_]/', '', (string) $route) ?: 'home';

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
