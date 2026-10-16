<?php

declare(strict_types=1);

auth_check();
csrf_verify();

$id = post_int('id');
$competitionId = post_int('competition_id');
$name = post_string('name');
$expertsCount = post_int('experts_count', 3);
$chiefExpertId = post_int('chief_expert_id');

if ($name === '' || $expertsCount < 3 || $expertsCount > 6) {
    flash_set('error', 'Проверьте параметры компетенции (эксперты 3–6).');
    redirect($id > 0 ? url('specialty_settings', ['id' => $id, 'tab' => 'general']) : url('specialty_edit', ['competition_id' => $competitionId]));
}

if ($id > 0) {
    auth_require_admin();
    $chiefValue = null;
    if ($chiefExpertId > 0) {
        $chief = db_fetch($pdo, "SELECT id FROM users WHERE id = ? AND role = 'chief_expert'", [$chiefExpertId]);
        if (!$chief) {
            flash_set('error', 'Выберите главного эксперта из списка.');
            redirect(url('specialty_settings', ['id' => $id, 'tab' => 'general']));
        }
        $chiefValue = $chiefExpertId;
    }
    db_exec(
        $pdo,
        'UPDATE specialties SET name = ?, experts_count = ?, chief_expert_id = ? WHERE id = ?',
        [$name, $expertsCount, $chiefValue, $id]
    );
    specialty_lock($id);
    flash_set('success', 'Компетенция сохранена.');
    redirect(url('specialty_settings', ['id' => $id, 'tab' => 'general']));
}

auth_require_admin();
db_exec(
    $pdo,
    'INSERT INTO specialties (competition_id, name, experts_count, sort_order) VALUES (?, ?, ?, 0)',
    [$competitionId, $name, $expertsCount]
);
$newId = (int) db_last_id($pdo);
flash_set('success', 'Компетенция создана. Назначьте главного эксперта во вкладке «Параметры».');
redirect(url('specialty_settings', ['id' => $newId, 'tab' => 'general']));
