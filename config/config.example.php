<?php

declare(strict_types=1);

/**
 * Скопируйте в config.php и укажите свои данные MySQL.
 *   copy config.example.php config.php
 */

return [
    'app_name' => 'Профессионалы — подсчёт результатов',
    'base_path' => '/profkpk',
    'session_name' => 'profkpk_session',
    'db' => [
        'host' => 'localhost',
        'name' => 'profkpk',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'default_admin' => [
        'login' => 'admin',
        'password' => 'admin123',
    ],
];
