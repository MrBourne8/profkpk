<?php

declare(strict_types=1);

auth_check();
csrf_verify();

$id = post_int('id');
$specialtyId = post_int('specialty_id');
$number = post_int('number');
$fullName = post_string('full_name');

if ($id <= 0 || $fullName === '' || $number < 1 || $number > 6) {
    flash_set('error', 'Проверьте данные эксперта.');
    redirect(url('specialty_settings', ['id' => $specialtyId, 'tab' => 'experts']));
}

try {
    db_exec(
        $pdo,
        'UPDATE experts SET number = ?, full_name = ? WHERE id = ? AND specialty_id = ?',
        [$number, $fullName, $id, $specialtyId]
    );
    flash_set('success', 'Эксперт сохранён.');
} catch (PDOException $e) {
    flash_set('error', 'Не удалось сохранить эксперта (возможно, номер занят).');
}

redirect(url('specialty_settings', ['id' => $specialtyId, 'tab' => 'experts']));
