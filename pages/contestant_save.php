<?php

declare(strict_types=1);

auth_check();
csrf_verify();

$id = post_int('id');
$specialtyId = post_int('specialty_id');
if ($id > 0) {
    $row = db_fetch($pdo, 'SELECT specialty_id FROM contestants WHERE id = ?', [$id]);
    if ($row) {
        $specialtyId = (int) $row['specialty_id'];
    }
}
specialty_assert_unlocked($pdo, $specialtyId);

$number = post_string('number');
$fullName = post_string('full_name');
$groupName = post_string('group_name') ?: null;
$sortOrder = post_int('sort_order');

if ($number === '' || $fullName === '') {
    flash_set('error', 'Укажите номер и ФИО.');
    redirect($id > 0 ? url('contestant_edit', ['id' => $id]) : url('contestant_edit', ['specialty_id' => $specialtyId]));
}

try {
    if ($id > 0) {
        db_exec(
            $pdo,
            'UPDATE contestants SET number = ?, full_name = ?, group_name = ?, sort_order = ? WHERE id = ?',
            [$number, $fullName, $groupName, $sortOrder, $id]
        );
    } else {
        db_exec(
            $pdo,
            'INSERT INTO contestants (specialty_id, number, full_name, group_name, sort_order) VALUES (?, ?, ?, ?, ?)',
            [$specialtyId, $number, $fullName, $groupName, $sortOrder]
        );
    }
    flash_set('success', 'Конкурсант сохранён.');
} catch (PDOException $e) {
    flash_set('error', 'Не удалось сохранить: возможно, номер уже используется.');
}

redirect(url('specialty_settings', ['id' => $specialtyId, 'tab' => 'contestants']));
