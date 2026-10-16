<?php

declare(strict_types=1);

auth_check();
csrf_verify();

$specialtyId = post_int('specialty_id');
$specialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [$specialtyId]);
if (!$specialty || !auth_can_access_specialty($specialty)) {
    flash_set('error', 'Нет доступа.');
    redirect(url('home'));
}
if (auth_is_admin()) {
    specialty_unlock($specialtyId);
} elseif (!empty($specialty['chief_expert_id']) && !specialty_is_unlocked($specialtyId)) {
    flash_set('error', 'Сначала откройте компетенцию паролем главного эксперта.');
    redirect(url('scores', ['id' => $specialtyId]));
}

$competition = specialty_competition($pdo, $specialtyId);
if (!competition_allows_scoring($competition)) {
    flash_set('error', 'Вносить оценки можно только при статусе конкурса «Активный».');
    redirect(url('specialty', ['id' => $specialtyId]));
}

$raw = $_POST['scores'] ?? [];
if (!is_array($raw)) {
    $raw = [];
}

$validContestants = db_fetch_all($pdo, 'SELECT id FROM contestants WHERE specialty_id = ?', [$specialtyId]);
$validCriteria = db_fetch_all(
    $pdo,
    'SELECT c.id, c.max_points, c.allowed_values FROM criteria c
     INNER JOIN modules m ON m.id = c.module_id
     WHERE m.specialty_id = ?',
    [$specialtyId]
);

$contestantIds = array_map(static fn($r) => (int) $r['id'], $validContestants);
$criteriaById = [];
foreach ($validCriteria as $c) {
    $criteriaById[(int) $c['id']] = $c;
}

$upsert = $pdo->prepare(
    'INSERT INTO scores (contestant_id, criterion_id, points)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE points = VALUES(points)'
);
$delete = $pdo->prepare(
    'DELETE FROM scores WHERE contestant_id = ? AND criterion_id = ?'
);

$pdo->beginTransaction();
try {
    foreach ($raw as $contestantId => $byCriterion) {
        $contestantId = (int) $contestantId;
        if (!in_array($contestantId, $contestantIds, true) || !is_array($byCriterion)) {
            continue;
        }
        foreach ($byCriterion as $criterionId => $pointsRaw) {
            $criterionId = (int) $criterionId;
            if (!isset($criteriaById[$criterionId])) {
                continue;
            }
            $criterion = $criteriaById[$criterionId];
            $pointsRaw = is_string($pointsRaw) || is_numeric($pointsRaw) ? trim((string) $pointsRaw) : '';
            if ($pointsRaw === '') {
                $delete->execute([$contestantId, $criterionId]);
                continue;
            }
            $points = (float) str_replace(',', '.', $pointsRaw);
            if (!criterion_is_allowed_score($criterion, $points)) {
                continue;
            }
            $upsert->execute([$contestantId, $criterionId, $points]);
        }
    }
    $pdo->commit();
    flash_set('success', 'Согласованные оценки сохранены.');
} catch (Throwable $e) {
    $pdo->rollBack();
    flash_set('error', 'Ошибка сохранения оценок: ' . $e->getMessage());
}

redirect(url('scores', ['id' => $specialtyId]));
