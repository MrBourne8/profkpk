<?php

declare(strict_types=1);

$id = get_int('id');
$specialty = specialty_require_access($pdo, $id);
$competition = specialty_competition($pdo, $id);

if (!competition_allows_scoring($competition)) {
    flash_set(
        'error',
        'Вносить оценки можно только когда конкурс в статусе «Активный». Сейчас: '
        . status_label($competition['status'] ?? 'draft') . '.'
    );
    redirect(url('specialty', ['id' => $id]));
}

$modules = db_fetch_all($pdo, 'SELECT * FROM modules WHERE specialty_id = ? ORDER BY sort_order, id', [$id]);
$criteriaByModule = [];
foreach ($modules as $module) {
    $criteriaByModule[(int) $module['id']] = db_fetch_all(
        $pdo,
        'SELECT * FROM criteria WHERE module_id = ? ORDER BY sort_order, id',
        [(int) $module['id']]
    );
}
$contestants = db_fetch_all($pdo, 'SELECT * FROM contestants WHERE specialty_id = ? ORDER BY sort_order, id', [$id]);

$scores = db_fetch_all(
    $pdo,
    'SELECT s.contestant_id, s.criterion_id, s.points
     FROM scores s
     INNER JOIN contestants c ON c.id = s.contestant_id
     WHERE c.specialty_id = ?',
    [$id]
);
$scoreMap = [];
foreach ($scores as $s) {
    $scoreMap[(int) $s['contestant_id']][(int) $s['criterion_id']] = (float) $s['points'];
}

render('scores', [
    'pageTitle' => 'Согласованные оценки',
    'specialty' => $specialty,
    'competition' => $competition,
    'modules' => $modules,
    'criteriaByModule' => $criteriaByModule,
    'contestants' => $contestants,
    'scoreMap' => $scoreMap,
]);
