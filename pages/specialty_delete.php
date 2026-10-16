<?php

declare(strict_types=1);

auth_require_admin();
csrf_verify();

$id = post_int('id');
$row = db_fetch($pdo, 'SELECT competition_id FROM specialties WHERE id = ?', [$id]);
db_exec($pdo, 'DELETE FROM specialties WHERE id = ?', [$id]);
flash_set('success', 'Компетенция удалена.');
redirect($row ? url('competition', ['id' => $row['competition_id']]) : url('home'));
