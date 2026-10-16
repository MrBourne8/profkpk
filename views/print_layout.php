<?php /** @var string $content */ ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Протокол') ?></title>
    <link rel="stylesheet" href="<?= e(rtrim($config['base_path'] ?? '', '/') . '/assets/style.css') ?>">
</head>
<body class="print-page">
<main class="container">
    <div class="no-print toolbar">
        <button type="button" class="btn btn-primary" onclick="window.print()">Печать</button>
        <a class="btn btn-ghost" href="<?= e($backUrl ?? url('home')) ?>">Назад</a>
    </div>
    <?= $content ?>
</main>
</body>
</html>
