<?php

declare(strict_types=1);

auth_check();
csrf_verify();

$id = post_int('id');
$row = db_fetch($pdo, 'SELECT specialty_id FROM modules WHERE id = ?', [$id]);
if (!$row) {
    flash_set('error', 'Модуль не найден.');
    redirect(url('home'));
}
specialty_assert_unlocked($pdo, (int) $row['specialty_id']);
db_exec($pdo, 'DELETE FROM modules WHERE id = ?', [$id]);
flash_set('success', 'Модуль удалён.');
redirect(url('specialty_settings', ['id' => $row['specialty_id'], 'tab' => 'criteria']));
