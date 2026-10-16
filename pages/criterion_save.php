<?php

declare(strict_types=1);

auth_check();
csrf_verify();

$id = post_int('id');
$moduleId = post_int('module_id');
$title = post_string('title');
$description = post_string('description') ?: null;
$sortOrder = post_int('sort_order');

$rawValues = $_POST['values'] ?? [];
if (!is_array($rawValues)) {
    $rawValues = [];
}
$allowed = criterion_parse_allowed_values($rawValues);

$module = db_fetch($pdo, 'SELECT * FROM modules WHERE id = ?', [$moduleId]);
if ($module) {
    specialty_assert_unlocked($pdo, (int) $module['specialty_id']);
}
if (!$module || $title === '') {
    flash_set('error', 'Проверьте данные критерия.');
    redirect(url('home'));
}

if ($allowed === []) {
    flash_set('error', 'Укажите хотя бы одно допустимое значение кроме 0.');
    redirect($id > 0 ? url('criterion_edit', ['id' => $id]) : url('criterion_edit', ['module_id' => $moduleId]));
}

$maxPoints = max($allowed);
$allowedJson = json_encode($allowed, JSON_UNESCAPED_UNICODE);

if ($id > 0) {
    db_exec(
        $pdo,
        'UPDATE criteria SET title = ?, description = ?, max_points = ?, allowed_values = ?, sort_order = ? WHERE id = ?',
        [$title, $description, $maxPoints, $allowedJson, $sortOrder, $id]
    );
} else {
    db_exec(
        $pdo,
        'INSERT INTO criteria (module_id, title, description, max_points, allowed_values, sort_order)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$moduleId, $title, $description, $maxPoints, $allowedJson, $sortOrder]
    );
}

flash_set('success', 'Критерий сохранён. Допустимые баллы: 0, ' . implode(', ', array_map('format_points', $allowed)));
redirect(url('specialty_settings', ['id' => $module['specialty_id'], 'tab' => 'criteria']));
