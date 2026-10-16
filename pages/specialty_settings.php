<?php

declare(strict_types=1);

$id = get_int('id');
$specialty = specialty_require_access($pdo, $id);

$tab = preg_replace('/[^a-z]/', '', (string) ($_GET['tab'] ?? 'contestants')) ?: 'contestants';
if (!in_array($tab, ['contestants', 'experts', 'criteria', 'general'], true)) {
    $tab = 'contestants';
}
// Параметры (назначение главного эксперта) — только администратор
if ($tab === 'general' && !auth_is_admin()) {
    $tab = 'contestants';
}

$competition = db_fetch($pdo, 'SELECT * FROM competitions WHERE id = ?', [(int) $specialty['competition_id']]);
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
$experts = db_fetch_all($pdo, 'SELECT * FROM experts WHERE specialty_id = ? ORDER BY number', [$id]);
$chiefExperts = auth_is_admin()
    ? db_fetch_all($pdo, "SELECT id, login, display_name FROM users WHERE role = 'chief_expert' ORDER BY display_name, login")
    : [];

render('specialty_settings', [
    'pageTitle' => 'Настройки — ' . $specialty['name'],
    'competition' => $competition,
    'specialty' => $specialty,
    'modules' => $modules,
    'criteriaByModule' => $criteriaByModule,
    'contestants' => $contestants,
    'experts' => $experts,
    'chiefExperts' => $chiefExperts,
    'tab' => $tab,
]);
