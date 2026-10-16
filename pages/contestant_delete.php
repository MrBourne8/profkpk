<?php

declare(strict_types=1);

auth_check();
csrf_verify();

$id = post_int('id');
$row = db_fetch($pdo, 'SELECT specialty_id FROM contestants WHERE id = ?', [$id]);
db_exec($pdo, 'DELETE FROM contestants WHERE id = ?', [$id]);
flash_set('success', 'Конкурсант удалён.');
redirect($row ? url('specialty_settings', ['id' => $row['specialty_id'], 'tab' => 'contestants']) : url('home'));
