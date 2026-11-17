<?php

declare(strict_types=1);

auth_check();
csrf_verify();
contestants_ensure_workplace_column($pdo);

$id = post_int('id');
$specialtyId = post_int('specialty_id');
if ($id > 0) {
    $row = db_fetch($pdo, 'SELECT specialty_id FROM contestants WHERE id = ?', [$id]);
    if ($row) {
        $specialtyId = (int) $row['specialty_id'];
    }
}
specialty_assert_unlocked($pdo, $specialtyId);

$fullName = post_string('full_name');
$groupName = post_string('group_name') ?: null;

if ($fullName === '') {
    flash_set('error', 'Укажите ФИО.');
    redirect($id > 0 ? url('contestant_edit', ['id' => $id]) : url('contestant_edit', ['specialty_id' => $specialtyId]));
}

try {
    if ($id > 0) {
        db_exec(
            $pdo,
            'UPDATE contestants SET full_name = ?, group_name = ? WHERE id = ?',
            [$fullName, $groupName, $id]
        );
        flash_set('success', 'Конкурсант сохранён.');
    } else {
        $maxRow = db_fetch(
            $pdo,
            'SELECT COALESCE(MAX(CAST(number AS UNSIGNED)), 0) AS m,
                    COALESCE(MAX(sort_order), 0) AS s
             FROM contestants WHERE specialty_id = ?',
            [$specialtyId]
        );
        $number = (string) (((int) ($maxRow['m'] ?? 0)) + 1);
        $sortOrder = ((int) ($maxRow['s'] ?? 0)) + 1;
        $workplace = contestant_draw_workplace($pdo, $specialtyId);
        db_exec(
            $pdo,
            'INSERT INTO contestants (specialty_id, number, full_name, group_name, workplace, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$specialtyId, $number, $fullName, $groupName, $workplace, $sortOrder]
        );
        flash_set('success', 'Конкурсант сохранён. По жеребьёвке назначено рабочее место №' . $workplace . '.');
    }
} catch (PDOException $e) {
    flash_set('error', 'Не удалось сохранить конкурсанта.');
}

redirect(url('specialty_settings', ['id' => $specialtyId, 'tab' => 'contestants']));
