<?php

declare(strict_types=1);

auth_check();

$id = get_int('id');
$specialtyId = get_int('specialty_id');
$module = null;

if ($id > 0) {
    $module = db_fetch($pdo, 'SELECT * FROM modules WHERE id = ?', [$id]);
    if (!$module) {
        flash_set('error', 'Модуль не найден.');
        redirect(url('home'));
    }
    $specialtyId = (int) $module['specialty_id'];
}

$specialty = specialty_require_access($pdo, $specialtyId);

render('module_edit', [
    'pageTitle' => $module ? 'Редактирование модуля' : 'Новый модуль',
    'specialty' => $specialty,
    'module' => $module,
]);
