<?php

declare(strict_types=1);

auth_check();

if (request_method() !== 'POST') {
    redirect(url('home'));
}

csrf_verify();

$specialtyId = post_int('specialty_id');
$password = (string) ($_POST['password'] ?? '');
$returnRoute = preg_replace('/[^a-z0-9_]/', '', post_string('return_route', 'specialty')) ?: 'specialty';
$returnParams = $_POST['return_params'] ?? [];
if (!is_array($returnParams)) {
    $returnParams = [];
}
$cleanParams = [];
foreach ($returnParams as $key => $value) {
    $key = preg_replace('/[^a-z0-9_]/', '', (string) $key) ?: '';
    if ($key === '' || !is_scalar($value)) {
        continue;
    }
    $cleanParams[$key] = (string) $value;
}

$specialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [$specialtyId]);
if (!$specialty || !auth_can_access_specialty($specialty)) {
    flash_set('error', 'Нет доступа к компетенции.');
    redirect(url('home'));
}

$chiefId = (int) ($specialty['chief_expert_id'] ?? 0);
$chief = $chiefId > 0
    ? db_fetch($pdo, 'SELECT * FROM users WHERE id = ? AND role = ?', [$chiefId, 'chief_expert'])
    : null;

if (!$chief || !password_verify($password, $chief['password_hash'])) {
    $competition = db_fetch($pdo, 'SELECT * FROM competitions WHERE id = ?', [(int) $specialty['competition_id']]);
    render('specialty_unlock', [
        'pageTitle' => 'Доступ к компетенции',
        'specialty' => $specialty,
        'competition' => $competition,
        'chief' => $chief ? [
            'id' => (int) $chief['id'],
            'login' => $chief['login'],
            'display_name' => $chief['display_name'],
        ] : null,
        'return_route' => $returnRoute,
        'return_params' => $cleanParams,
        'unlockError' => 'Неверный пароль главного эксперта.',
    ]);
    exit;
}

specialty_unlock($specialtyId);
flash_set('success', 'Доступ к компетенции открыт.');

if ($returnRoute === 'print_expert_blank' && empty($cleanParams['specialty_id'])) {
    $cleanParams['specialty_id'] = (string) $specialtyId;
}

if (in_array($returnRoute, ['specialty', 'specialty_settings', 'scores', 'results', 'print_results'], true) && empty($cleanParams['id'])) {
    $cleanParams['id'] = (string) $specialtyId;
}

redirect(url($returnRoute, $cleanParams));
