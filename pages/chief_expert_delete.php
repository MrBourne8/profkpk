<?php

declare(strict_types=1);

auth_require_admin();
csrf_verify();

$id = post_int('id');
db_exec($pdo, "UPDATE specialties SET chief_expert_id = NULL WHERE chief_expert_id = ?", [$id]);
db_exec($pdo, "DELETE FROM users WHERE id = ? AND role = 'chief_expert'", [$id]);
flash_set('success', 'Главный эксперт удалён.');
redirect(url('chief_experts'));
