<?php

declare(strict_types=1);

/**
 * Баллы конкурсанта по модулю = сумма согласованных оценок по критериям модуля.
 *
 * @param list<int> $criteriaIds
 * @param array<int, float|null> $scoresByCriterion [criterion_id => points]
 */
function module_score_for_contestant(array $criteriaIds, array $scoresByCriterion): ?float
{
    $total = 0.0;
    $hasAny = false;
    foreach ($criteriaIds as $criterionId) {
        if (!array_key_exists($criterionId, $scoresByCriterion) || $scoresByCriterion[$criterionId] === null) {
            continue;
        }
        $total += (float) $scoresByCriterion[$criterionId];
        $hasAny = true;
    }
    return $hasAny ? $total : null;
}

/**
 * Полный расчёт результатов специальности.
 *
 * @return array{
 *   specialty: ?array,
 *   contestants: list<array>,
 *   modules: list<array>,
 *   criteria_by_module: array<int, list<array>>,
 *   rows: list<array{contestant: array, module_scores: array<int, float|null>, total: float|null, place: int|null}>,
 *   score_map: array<int, array<int, float>>
 * }
 */
function calculate_specialty_results(PDO $pdo, int $specialtyId): array
{
    $specialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [$specialtyId]);
    if (!$specialty) {
        return [
            'contestants' => [],
            'modules' => [],
            'criteria_by_module' => [],
            'rows' => [],
            'specialty' => null,
            'score_map' => [],
        ];
    }

    $modules = db_fetch_all(
        $pdo,
        'SELECT * FROM modules WHERE specialty_id = ? ORDER BY sort_order, id',
        [$specialtyId]
    );
    $contestants = db_fetch_all(
        $pdo,
        'SELECT * FROM contestants WHERE specialty_id = ? ORDER BY sort_order, id',
        [$specialtyId]
    );

    $criteriaByModule = [];
    foreach ($modules as $module) {
        $criteriaByModule[(int) $module['id']] = db_fetch_all(
            $pdo,
            'SELECT * FROM criteria WHERE module_id = ? ORDER BY sort_order, id',
            [(int) $module['id']]
        );
    }

    $allScores = db_fetch_all(
        $pdo,
        'SELECT s.contestant_id, s.criterion_id, s.points
         FROM scores s
         INNER JOIN contestants c ON c.id = s.contestant_id
         WHERE c.specialty_id = ?',
        [$specialtyId]
    );

    // [contestant_id][criterion_id] => points (согласованная оценка)
    $map = [];
    foreach ($allScores as $score) {
        $map[(int) $score['contestant_id']][(int) $score['criterion_id']] = (float) $score['points'];
    }

    $rows = [];
    foreach ($contestants as $contestant) {
        $cid = (int) $contestant['id'];
        $moduleScores = [];
        $total = 0.0;
        $hasTotal = false;

        foreach ($modules as $module) {
            $mid = (int) $module['id'];
            $criteria = $criteriaByModule[$mid] ?? [];
            $criteriaIds = array_map(static fn($c) => (int) $c['id'], $criteria);
            $byCriterion = [];
            foreach ($criteriaIds as $critId) {
                $byCriterion[$critId] = $map[$cid][$critId] ?? null;
            }
            $ms = module_score_for_contestant($criteriaIds, $byCriterion);
            $moduleScores[$mid] = $ms;
            if ($ms !== null) {
                $total += $ms;
                $hasTotal = true;
            }
        }

        $rows[] = [
            'contestant' => $contestant,
            'module_scores' => $moduleScores,
            'total' => $hasTotal ? $total : null,
            'place' => null,
        ];
    }

    usort($rows, static function (array $a, array $b): int {
        $ta = $a['total'];
        $tb = $b['total'];
        if ($ta === null && $tb === null) {
            return ((int) $a['contestant']['id']) <=> ((int) $b['contestant']['id']);
        }
        if ($ta === null) {
            return 1;
        }
        if ($tb === null) {
            return -1;
        }
        if ($tb == $ta) {
            return ((int) $a['contestant']['id']) <=> ((int) $b['contestant']['id']);
        }
        return $tb <=> $ta;
    });

    $place = 0;
    $index = 0;
    $prevTotal = null;
    foreach ($rows as &$row) {
        $index++;
        if ($row['total'] === null) {
            $row['place'] = null;
            continue;
        }
        if ($prevTotal === null || abs($row['total'] - $prevTotal) > 0.00001) {
            $place = $index;
            $prevTotal = $row['total'];
        }
        $row['place'] = $place;
    }
    unset($row);

    return [
        'specialty' => $specialty,
        'contestants' => $contestants,
        'modules' => $modules,
        'criteria_by_module' => $criteriaByModule,
        'rows' => $rows,
        'score_map' => $map,
    ];
}

function specialty_has_scores(PDO $pdo, int $specialtyId): bool
{
    $row = db_fetch(
        $pdo,
        'SELECT COUNT(*) AS cnt FROM scores s
         INNER JOIN contestants c ON c.id = s.contestant_id
         WHERE c.specialty_id = ?',
        [$specialtyId]
    );
    return $row && (int) $row['cnt'] > 0;
}
