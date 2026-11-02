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
        // В OSPanel MySQL обычно на 127.0.1.30 (не localhost / 127.0.0.1)
        'host' => '127.0.1.30',
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
