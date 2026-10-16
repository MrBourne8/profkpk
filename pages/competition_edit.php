<?php

declare(strict_types=1);

auth_require_admin();

$id = get_int('id');
$competition = null;
if ($id > 0) {
    $competition = db_fetch($pdo, 'SELECT * FROM competitions WHERE id = ?', [$id]);
    if (!$competition) {
        flash_set('error', 'Конкурс не найден.');
        redirect(url('home'));
    }
}

render('competition_edit', [
    'pageTitle' => $competition ? 'Редактирование конкурса' : 'Новый конкурс',
    'competition' => $competition,
]);
