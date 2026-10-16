<?php

declare(strict_types=1);

auth_require_admin();
csrf_verify();

$id = post_int('id');
$title = post_string('title');
$start = post_string('start_date') ?: null;
$end = post_string('end_date') ?: null;
$status = post_string('status', 'draft');
$notes = post_string('notes') ?: null;

if ($title === '' || !in_array($status, ['draft', 'active', 'finished'], true)) {
    flash_set('error', 'Проверьте заполнение формы.');
    redirect($id > 0 ? url('competition_edit', ['id' => $id]) : url('competition_edit'));
}

if ($id > 0) {
    db_exec(
        $pdo,
        'UPDATE competitions SET title = ?, start_date = ?, end_date = ?, status = ?, notes = ? WHERE id = ?',
        [$title, $start, $end, $status, $notes, $id]
    );
    flash_set('success', 'Конкурс сохранён.');
    redirect(url('competition', ['id' => $id]));
}

db_exec(
    $pdo,
    'INSERT INTO competitions (title, start_date, end_date, status, notes) VALUES (?, ?, ?, ?, ?)',
    [$title, $start, $end, $status, $notes]
);
$newId = (int) db_last_id($pdo);
flash_set('success', 'Конкурс создан.');
redirect(url('competition', ['id' => $newId]));
