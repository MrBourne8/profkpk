<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';

session_name($config['session_name']);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/scoring.php';
require_once __DIR__ . '/nav.php';

$pdo = db_connect($config['db']);
