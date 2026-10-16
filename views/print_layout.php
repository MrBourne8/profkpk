<?php
/** @var string $content */
$printLandscape = !empty($printLandscape);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Протокол') ?></title>
    <link rel="stylesheet" href="<?= e(rtrim($config['base_path'] ?? '', '/') . '/assets/style.css') ?>">
    <?php if ($printLandscape): ?>
        <style>
            @page {
                size: A4 landscape;
                margin: 6mm;
            }
            /* Подсказка в диалоге печати: альбомная ориентация */
            html, body, .print-container, .print-sheet, .print-table {
                width: 100% !important;
                max-width: none !important;
            }
        </style>
    <?php endif; ?>
</head>
<body class="print-page<?= $printLandscape ? ' print-landscape' : '' ?>">
<main class="print-container">
    <div class="no-print toolbar">
        <button type="button" class="btn btn-primary" onclick="window.print()">Печать</button>
        <a class="btn btn-ghost" href="<?= e($backUrl ?? url('home')) ?>">Назад</a>
    </div>
    <?= $content ?>
</main>
</body>
</html>
