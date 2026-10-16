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
db_exec($pdo, 'DELETE FROM criteria WHERE id = ?', [$id]);
flash_set('success', 'Критерий удалён.');
redirect($row ? url('specialty_settings', ['id' => $row['specialty_id'], 'tab' => 'criteria']) : url('home'));
