<?php

declare(strict_types=1);

auth_check();
csrf_verify();

$id = post_int('id');
$specialtyId = post_int('specialty_id');
if ($id > 0) {
    $row = db_fetch($pdo, 'SELECT specialty_id FROM modules WHERE id = ?', [$id]);
    if ($row) {
        $specialtyId = (int) $row['specialty_id'];
    }
}
specialty_assert_unlocked($pdo, $specialtyId);
$title = post_string('title');
$description = post_string('description') ?: null;
$maxScore = post_float('max_score');
$sortOrder = post_int('sort_order');

if ($title === '') {
    flash_set('error', 'Укажите название модуля.');
    redirect($id > 0 ? url('module_edit', ['id' => $id]) : url('module_edit', ['specialty_id' => $specialtyId]));
}

if ($id > 0) {
    db_exec(
        $pdo,
        'UPDATE modules SET title = ?, description = ?, max_score = ?, sort_order = ? WHERE id = ?',
        [$title, $description, $maxScore, $sortOrder, $id]
    );
} else {
    db_exec(
        $pdo,
        'INSERT INTO modules (specialty_id, title, description, max_score, sort_order) VALUES (?, ?, ?, ?, ?)',
        [$specialtyId, $title, $description, $maxScore, $sortOrder]
    );
}

flash_set('success', 'Модуль сохранён.');
redirect(url('specialty_settings', ['id' => $specialtyId, 'tab' => 'criteria']));
