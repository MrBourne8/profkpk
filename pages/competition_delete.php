<?php

declare(strict_types=1);

auth_require_admin();
csrf_verify();

$id = post_int('id');
db_exec($pdo, 'DELETE FROM competitions WHERE id = ?', [$id]);
flash_set('success', 'Конкурс удалён.');
redirect(url('home'));
