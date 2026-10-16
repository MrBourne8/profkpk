<?php

declare(strict_types=1);

$id = get_int('id');
specialty_require_access($pdo, $id);

$result = calculate_specialty_results($pdo, $id);
if (!$result['specialty']) {
    flash_set('error', 'Компетенция не найдена.');
    redirect(url('home'));
}

$competition = db_fetch(
    $pdo,
    'SELECT * FROM competitions WHERE id = ?',
    [(int) $result['specialty']['competition_id']]
);

render('specialty', [
    'pageTitle' => $result['specialty']['name'],
    'competition' => $competition,
    'specialty' => $result['specialty'],
    'modules' => $result['modules'],
    'rows' => $result['rows'],
]);
