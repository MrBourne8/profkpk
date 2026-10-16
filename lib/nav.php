<?php

declare(strict_types=1);

/**
 * Контекст бокового меню: конкурсы → компетенции → действия.
 *
 * @return array{
 *   competitions: list<array>,
 *   current_competition: ?array,
 *   specialties: list<array>,
 *   current_specialty: ?array,
 *   route: string
 * }
 */
function nav_context(PDO $pdo): array
{
    $route = preg_replace('/[^a-z0-9_]/', '', (string) ($_GET['r'] ?? 'home')) ?: 'home';

    $competitionId = get_int('competition_id');
    $specialtyId = get_int('id');
    $moduleId = get_int('module_id');

    // Для маршрутов, где id — не специальность
    $specialtyRoutes = [
        'specialty', 'specialty_settings', 'specialty_edit', 'scores', 'results',
        'print_results', 'contestant_edit', 'expert_edit',
    ];
    $competitionRoutes = ['competition', 'competition_edit', 'specialty_edit'];

    $currentSpecialty = null;
    $currentCompetition = null;

    if (in_array($route, $specialtyRoutes, true) && $specialtyId > 0 && $route !== 'specialty_edit') {
        // specialty_edit: id = specialty; specialty_edit new: competition_id
        if ($route === 'contestant_edit' || $route === 'expert_edit') {
            // id может быть contestant/expert — ниже уточним
        } else {
            $currentSpecialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [$specialtyId]);
        }
    }

    if ($route === 'specialty_edit') {
        if ($specialtyId > 0) {
            $currentSpecialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [$specialtyId]);
        }
        if (!$currentSpecialty && $competitionId > 0) {
            $currentCompetition = db_fetch($pdo, 'SELECT * FROM competitions WHERE id = ?', [$competitionId]);
        }
    }

    if ($route === 'contestant_edit' && $specialtyId > 0) {
        // id = contestant при редактировании
        $c = db_fetch($pdo, 'SELECT * FROM contestants WHERE id = ?', [$specialtyId]);
        if ($c) {
            $currentSpecialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [(int) $c['specialty_id']]);
        } elseif (get_int('specialty_id') > 0) {
            $currentSpecialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [get_int('specialty_id')]);
        }
    }

    if ($route === 'expert_edit') {
        $eid = get_int('id');
        if ($eid > 0) {
            $ex = db_fetch($pdo, 'SELECT * FROM experts WHERE id = ?', [$eid]);
            if ($ex) {
                $currentSpecialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [(int) $ex['specialty_id']]);
            }
        }
        if (!$currentSpecialty && get_int('specialty_id') > 0) {
            $currentSpecialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [get_int('specialty_id')]);
        }
    }

    if (in_array($route, ['module_edit', 'criterion_edit', 'print_expert_blank', 'print_blank_choose'], true)) {
        if ($moduleId > 0) {
            $module = db_fetch($pdo, 'SELECT * FROM modules WHERE id = ?', [$moduleId]);
            if ($module) {
                $currentSpecialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [(int) $module['specialty_id']]);
            }
        }
        if ($route === 'criterion_edit' && get_int('id') > 0) {
            $cr = db_fetch($pdo, 'SELECT m.specialty_id FROM criteria c INNER JOIN modules m ON m.id = c.module_id WHERE c.id = ?', [get_int('id')]);
            if ($cr) {
                $currentSpecialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [(int) $cr['specialty_id']]);
            }
        }
        if ($route === 'module_edit' && get_int('id') > 0 && !$currentSpecialty) {
            $module = db_fetch($pdo, 'SELECT * FROM modules WHERE id = ?', [get_int('id')]);
            if ($module) {
                $currentSpecialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [(int) $module['specialty_id']]);
            }
        }
        if ($route === 'print_expert_blank' && get_int('specialty_id') > 0) {
            $currentSpecialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [get_int('specialty_id')]);
        }
        if ($route === 'module_edit' && get_int('specialty_id') > 0 && !$currentSpecialty) {
            $currentSpecialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [get_int('specialty_id')]);
        }
    }

    if (in_array($route, ['specialty', 'specialty_settings', 'scores', 'results', 'print_results'], true) && !$currentSpecialty && $specialtyId > 0) {
        $currentSpecialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [$specialtyId]);
    }

    if ($currentSpecialty) {
        $currentCompetition = db_fetch(
            $pdo,
            'SELECT * FROM competitions WHERE id = ?',
            [(int) $currentSpecialty['competition_id']]
        );
    } elseif (in_array($route, $competitionRoutes, true) && !$currentCompetition) {
        $cid = $competitionId > 0 ? $competitionId : $specialtyId;
        if ($route === 'competition' || $route === 'competition_edit') {
            $cid = $specialtyId > 0 ? $specialtyId : $competitionId;
        }
        if ($cid > 0) {
            $currentCompetition = db_fetch($pdo, 'SELECT * FROM competitions WHERE id = ?', [$cid]);
        }
    }

    $competitions = [];
    $specialties = [];
    try {
        if (auth_is_chief()) {
            $uid = (int) auth_user()['id'];
            $competitions = db_fetch_all(
                $pdo,
                'SELECT DISTINCT c.id, c.title, c.status
                 FROM competitions c
                 INNER JOIN specialties s ON s.competition_id = c.id
                 WHERE s.chief_expert_id = ?
                 ORDER BY c.id DESC',
                [$uid]
            );
            if ($currentCompetition) {
                $specialties = db_fetch_all(
                    $pdo,
                    'SELECT id, name FROM specialties
                     WHERE competition_id = ? AND chief_expert_id = ?
                     ORDER BY sort_order, id',
                    [(int) $currentCompetition['id'], $uid]
                );
            }
            // Главный эксперт не должен видеть чужую текущую компетенцию в меню
            if ($currentSpecialty && (int) ($currentSpecialty['chief_expert_id'] ?? 0) !== $uid) {
                $currentSpecialty = null;
            }
        } else {
            $competitions = db_fetch_all($pdo, 'SELECT id, title, status FROM competitions ORDER BY id DESC');
            if ($currentCompetition) {
                $specialties = db_fetch_all(
                    $pdo,
                    'SELECT id, name FROM specialties WHERE competition_id = ? ORDER BY sort_order, id',
                    [(int) $currentCompetition['id']]
                );
            }
        }
    } catch (Throwable $e) {
        // БД ещё не готова
    }

    return [
        'competitions' => $competitions,
        'current_competition' => $currentCompetition,
        'specialties' => $specialties,
        'current_specialty' => $currentSpecialty,
        'route' => $route,
    ];
}
