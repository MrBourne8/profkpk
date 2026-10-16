<?php

declare(strict_types=1);

auth_check();
csrf_verify();

$id = post_int('id');
$row = db_fetch($pdo, 'SELECT specialty_id FROM modules WHERE id = ?', [$id]);
db_exec($pdo, 'DELETE FROM modules WHERE id = ?', [$id]);
flash_set('success', 'Модуль удалён.');
redirect($row ? url('specialty_settings', ['id' => $row['specialty_id'], 'tab' => 'criteria']) : url('home'));
