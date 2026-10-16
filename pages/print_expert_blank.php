<?php

declare(strict_types=1);

$specialtyId = get_int('specialty_id');
$moduleId = get_int('module_id');
$expertId = get_int('expert_id');
$format = preg_replace('/[^a-z_]/', '', (string) ($_GET['format'] ?? '')) ?: '';

$modules = [];

if ($moduleId > 0) {
    $module = db_fetch($pdo, 'SELECT * FROM modules WHERE id = ?', [$moduleId]);
    if (!$module) {
        flash_set('error', 'Модуль не найден.');
        redirect(url('home'));
    }
    $specialtyId = (int) $module['specialty_id'];
    $modules = [$module];
}

$specialty = specialty_require_access($pdo, $specialtyId);

if (!$modules) {
    $modules = db_fetch_all(
        $pdo,
        'SELECT * FROM modules WHERE specialty_id = ? ORDER BY sort_order, id',
        [$specialtyId]
    );
}

$competition = db_fetch($pdo, 'SELECT * FROM competitions WHERE id = ?', [(int) $specialty['competition_id']]);
$contestants = db_fetch_all(
    $pdo,
    'SELECT * FROM contestants WHERE specialty_id = ? ORDER BY sort_order, id',
    [$specialtyId]
);
$experts = db_fetch_all($pdo, 'SELECT * FROM experts WHERE specialty_id = ? ORDER BY number', [$specialtyId]);

$criteriaByModule = [];
foreach ($modules as $module) {
    $criteriaByModule[(int) $module['id']] = db_fetch_all(
        $pdo,
        'SELECT * FROM criteria WHERE module_id = ? ORDER BY sort_order, id',
        [(int) $module['id']]
    );
}

if ($expertId > 0) {
    $one = db_fetch($pdo, 'SELECT * FROM experts WHERE id = ? AND specialty_id = ?', [$expertId, $specialtyId]);
    $printExperts = $one ? [$one] : [];
} elseif ($experts) {
    $printExperts = $experts;
} else {
    $printExperts = [];
    for ($i = 1; $i <= (int) $specialty['experts_count']; $i++) {
        $printExperts[] = [
            'id' => 0,
            'number' => $i,
            'full_name' => '______________________________',
        ];
    }
}

$backUrl = url('specialty', ['id' => $specialtyId]);

// Экран выбора формата
if (!in_array($format, ['per_contestant', 'matrix'], true)) {
    render('print_blank_choose', [
        'pageTitle' => 'Бланки экспертов',
        'competition' => $competition,
        'specialty' => $specialty,
        'modules' => $modules,
        'contestants' => $contestants,
        'printExperts' => $printExperts,
        'moduleId' => $moduleId,
        'expertId' => $expertId,
    ]);
    exit;
}

$view = $format === 'matrix' ? 'print_expert_blank_matrix' : 'print_expert_blank_per_contestant';

render($view, [
    'pageTitle' => $format === 'matrix' ? 'Бланк: критерии × конкурсанты' : 'Бланк: по конкурсанту',
    'competition' => $competition,
    'specialty' => $specialty,
    'modules' => $modules,
    'criteriaByModule' => $criteriaByModule,
    'contestants' => $contestants,
    'printExperts' => $printExperts,
    'format' => $format,
    'backUrl' => url('print_expert_blank', array_filter([
        'specialty_id' => $specialtyId,
        'module_id' => $moduleId ?: null,
        'expert_id' => $expertId ?: null,
    ])),
], 'print_layout');
