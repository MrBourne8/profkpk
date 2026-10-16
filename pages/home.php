<?php

declare(strict_types=1);

auth_check();

if (auth_is_chief()) {
    $uid = (int) auth_user()['id'];
    $competitions = db_fetch_all(
        $pdo,
        'SELECT c.*,
                (SELECT COUNT(*) FROM specialties s2
                 WHERE s2.competition_id = c.id AND s2.chief_expert_id = ?) AS specialty_count
         FROM competitions c
         WHERE EXISTS (
             SELECT 1 FROM specialties s
             WHERE s.competition_id = c.id AND s.chief_expert_id = ?
         )
         ORDER BY c.start_date DESC, c.id DESC',
        [$uid, $uid]
    );
} else {
    $competitions = db_fetch_all(
        $pdo,
        'SELECT c.*,
                (SELECT COUNT(*) FROM specialties s WHERE s.competition_id = c.id) AS specialty_count
         FROM competitions c
         ORDER BY c.start_date DESC, c.id DESC'
    );
}

render('home', [
    'pageTitle' => 'Конкурсы',
    'competitions' => $competitions,
]);
