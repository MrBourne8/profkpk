<?php

declare(strict_types=1);

auth_require_admin();

$root = dirname(__DIR__);
$checks = [
    'views/print_expert_blank_matrix.php' => [
        'ok' => 'format_points(max(criterion_score_options',
        'old' => 'criterion_format_allowed_label',
        'label' => 'Бланк эксперта (матрица): только макс. балл',
    ],
    'views/print_expert_blank_per_contestant.php' => [
        'ok' => 'format_points(max(criterion_score_options',
        'old' => 'criterion_format_allowed_label',
        'label' => 'Бланк эксперта (по конкурсанту): только макс. балл',
    ],
    'views/print_results.php' => [
        'ok' => 'Главный эксперт',
        'old' => 'Секретарь',
        'label' => 'Итоговый протокол: без секретаря',
        'old_must_absent' => true,
    ],
];

$rows = [];
foreach ($checks as $rel => $rule) {
    $path = $root . '/' . $rel;
    $exists = is_file($path);
    $body = $exists ? (string) file_get_contents($path) : '';
    $hasOk = $exists && str_contains($body, $rule['ok']);
    $hasOld = $exists && str_contains($body, $rule['old']);
    $pass = !empty($rule['old_must_absent'])
        ? ($hasOk && !$hasOld)
        : $hasOk;

    $rows[] = [
        'rel' => $rel,
        'path' => $path,
        'mtime' => $exists ? date('d.m.Y H:i:s', (int) filemtime($path)) : '—',
        'size' => $exists ? filesize($path) : 0,
        'pass' => $pass,
        'detail' => !$exists
            ? 'файл не найден'
            : ($pass ? 'ок' : ($hasOld ? 'старая версия' : 'нет нужного кода')),
        'label' => $rule['label'],
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Проверка деплоя</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem; }
        table { border-collapse: collapse; width: 100%; max-width: 960px; }
        th, td { border: 1px solid #ccc; padding: 0.5rem 0.75rem; text-align: left; vertical-align: top; }
        .ok { color: #0a7; font-weight: 700; }
        .bad { color: #c00; font-weight: 700; }
        code { font-size: 0.85em; }
    </style>
</head>
<body>
<h1>Проверка файлов на сервере</h1>
<p>Корень сайта: <code><?= e($root) ?></code></p>
<table>
    <thead>
    <tr>
        <th>Проверка</th>
        <th>Файл</th>
        <th>Изменён</th>
        <th>Статус</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['label']) ?></td>
            <td>
                <code><?= e($row['rel']) ?></code><br>
                <small><?= e($row['path']) ?></small>
            </td>
            <td><?= e($row['mtime']) ?><br><small><?= (int) $row['size'] ?> байт</small></td>
            <td class="<?= $row['pass'] ? 'ok' : 'bad' ?>"><?= e($row['detail']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p>
    Если статус «старая версия» — файлы залиты не в эту папку
    (<code><?= e($root) ?></code>).
</p>
<p><a href="<?= e(url('home')) ?>">← На главную</a></p>
</body>
</html>
