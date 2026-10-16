<?php

declare(strict_types=1);

auth_check();

$id = get_int('id');
$specialtyId = get_int('specialty_id');
$expert = null;

if ($id > 0) {
    $expert = db_fetch($pdo, 'SELECT * FROM experts WHERE id = ?', [$id]);
    if (!$expert) {
        flash_set('error', 'Эксперт не найден.');
        redirect(url('home'));
    }
    $specialtyId = (int) $expert['specialty_id'];
}

$specialty = specialty_require_access($pdo, $specialtyId);

$experts = db_fetch_all($pdo, 'SELECT * FROM experts WHERE specialty_id = ? ORDER BY number', [$specialtyId]);

render('expert_edit', [
    'pageTitle' => 'Эксперты',
    'specialty' => $specialty,
    'expert' => $expert,
    'experts' => $experts,
    'syncMode' => $expert === null,
]);
