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

// Совместимость, если на хостинге не обновили helpers.php
if (!function_exists('contestants_ensure_workplace_column')) {
    function contestants_ensure_workplace_column(PDO $pdo): void
    {
        try {
            $col = $pdo->query("SHOW COLUMNS FROM contestants LIKE 'workplace'")->fetch();
            if (!$col) {
                $pdo->exec('ALTER TABLE contestants ADD COLUMN workplace INT UNSIGNED NULL AFTER group_name');
            }
        } catch (Throwable $e) {
        }
    }
}
if (!function_exists('contestant_workplace_label')) {
    function contestant_workplace_label(?array $contestant): string
    {
        $wp = $contestant['workplace'] ?? null;
        if ($wp === null || $wp === '') {
            return '—';
        }
        return 'РМ ' . (int) $wp;
    }
}
if (!function_exists('criterion_format_max_points_label')) {
    function criterion_format_max_points_label(array $criterion): string
    {
        if (function_exists('criterion_score_options') && function_exists('format_points')) {
            $opts = criterion_score_options($criterion);
            return format_points($opts ? (float) max($opts) : 0.0);
        }
        return '0';
    }
}

$pdo = db_connect($config['db']);
