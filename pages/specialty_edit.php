<?php

declare(strict_types=1);

auth_require_admin();

$id = get_int('id');
$competitionId = get_int('competition_id');

if ($id > 0) {
    redirect(url('specialty_settings', ['id' => $id, 'tab' => 'general']));
}

$competition = db_fetch($pdo, 'SELECT * FROM competitions WHERE id = ?', [$competitionId]);
if (!$competition) {
    flash_set('error', 'Конкурс не найден.');
    redirect(url('home'));
}

render('specialty_edit', [
    'pageTitle' => 'Новая компетенция',
    'competition' => $competition,
    'specialty' => null,
]);
