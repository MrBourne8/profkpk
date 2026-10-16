<?php

declare(strict_types=1);

auth_require_admin();

$id = get_int('id');
$expert = null;
if ($id > 0) {
    $expert = db_fetch($pdo, "SELECT * FROM users WHERE id = ? AND role = 'chief_expert'", [$id]);
    if (!$expert) {
        flash_set('error', 'Главный эксперт не найден.');
        redirect(url('chief_experts'));
    }
}

render('chief_expert_edit', [
    'pageTitle' => $expert ? 'Редактирование главного эксперта' : 'Новый главный эксперт',
    'expert' => $expert,
]);
