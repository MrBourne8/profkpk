<?php

declare(strict_types=1);

auth_check();
csrf_verify();

$id = post_int('id');
$row = db_fetch(
    $pdo,
    'SELECT m.specialty_id FROM criteria c INNER JOIN modules m ON m.id = c.module_id WHERE c.id = ?',
    [$id]
);
if (!$row) {
    flash_set('error', 'Аспект не найден.');
    redirect(url('home'));
}
specialty_assert_unlocked($pdo, (int) $row['specialty_id']);
db_exec($pdo, 'DELETE FROM criteria WHERE id = ?', [$id]);
flash_set('success', 'Аспект удалён.');
redirect(url('specialty_settings', ['id' => $row['specialty_id'], 'tab' => 'criteria']));
