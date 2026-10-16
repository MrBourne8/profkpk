<?php

declare(strict_types=1);

auth_require_admin();

$experts = db_fetch_all(
    $pdo,
    "SELECT u.*,
            (SELECT COUNT(*) FROM specialties s WHERE s.chief_expert_id = u.id) AS specialty_count
     FROM users u
     WHERE u.role = 'chief_expert'
     ORDER BY u.display_name, u.login"
);

render('chief_experts', [
    'pageTitle' => 'Главные эксперты',
    'experts' => $experts,
]);
