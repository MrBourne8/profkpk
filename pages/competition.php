<?php

declare(strict_types=1);

auth_check();

$id = get_int('id');
$competition = db_fetch($pdo, 'SELECT * FROM competitions WHERE id = ?', [$id]);
if (!$competition) {
    flash_set('error', 'Конкурс не найден.');
    redirect(url('home'));
}

if (auth_is_chief()) {
    $uid = (int) auth_user()['id'];
    $specialties = db_fetch_all(
        $pdo,
        'SELECT s.*,
                (SELECT COUNT(*) FROM modules m WHERE m.specialty_id = s.id) AS module_count,
                (SELECT COUNT(*) FROM contestants c WHERE c.specialty_id = s.id) AS contestant_count
         FROM specialties s
         WHERE s.competition_id = ? AND s.chief_expert_id = ?
         ORDER BY s.sort_order, s.id',
        [$id, $uid]
    );
    if (!$specialties) {
        flash_set('error', 'Нет доступных компетенций в этом конкурсе.');
        redirect(url('home'));
    }
} else {
    $specialties = db_fetch_all(
        $pdo,
        'SELECT s.*,
                (SELECT COUNT(*) FROM modules m WHERE m.specialty_id = s.id) AS module_count,
                (SELECT COUNT(*) FROM contestants c WHERE c.specialty_id = s.id) AS contestant_count
         FROM specialties s
         WHERE s.competition_id = ?
         ORDER BY s.sort_order, s.id',
        [$id]
    );
}

render('competition', [
    'pageTitle' => $competition['title'],
    'competition' => $competition,
    'specialties' => $specialties,
]);
