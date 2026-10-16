<?php

declare(strict_types=1);

auth_check();

$id = get_int('id');
$moduleId = get_int('module_id');
$criterion = null;

if ($id > 0) {
    $criterion = db_fetch($pdo, 'SELECT * FROM criteria WHERE id = ?', [$id]);
    if (!$criterion) {
        flash_set('error', 'Критерий не найден.');
        redirect(url('home'));
    }
    $moduleId = (int) $criterion['module_id'];
}

$module = db_fetch($pdo, 'SELECT * FROM modules WHERE id = ?', [$moduleId]);
if (!$module) {
    flash_set('error', 'Модуль не найден.');
    redirect(url('home'));
}
$specialty = specialty_require_access($pdo, (int) $module['specialty_id']);

$moduleCriteria = db_fetch_all(
    $pdo,
    'SELECT id FROM criteria WHERE module_id = ? ORDER BY sort_order, id',
    [$moduleId]
);
$aspectNumber = count($moduleCriteria) + 1;
if ($criterion) {
    foreach ($moduleCriteria as $i => $row) {
        if ((int) $row['id'] === (int) $criterion['id']) {
            $aspectNumber = $i + 1;
            break;
        }
    }
}

render('criterion_edit', [
    'pageTitle' => $criterion ? 'Редактирование критерия' : 'Новый критерий',
    'specialty' => $specialty,
    'module' => $module,
    'criterion' => $criterion,
    'aspectNumber' => $aspectNumber,
]);
