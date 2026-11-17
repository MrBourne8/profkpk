<?php

declare(strict_types=1);

auth_check();
contestants_ensure_workplace_column($pdo);

$id = get_int('id');
$specialtyId = get_int('specialty_id');
$contestant = null;

if ($id > 0) {
    $contestant = db_fetch($pdo, 'SELECT * FROM contestants WHERE id = ?', [$id]);
    if (!$contestant) {
        flash_set('error', 'Конкурсант не найден.');
        redirect(url('home'));
    }
    $specialtyId = (int) $contestant['specialty_id'];
}

$specialty = specialty_require_access($pdo, $specialtyId);

render('contestant_edit', [
    'pageTitle' => $contestant ? 'Редактирование конкурсанта' : 'Новый конкурсант',
    'specialty' => $specialty,
    'contestant' => $contestant,
]);
