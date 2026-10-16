<?php

declare(strict_types=1);

function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function auth_check(): void
{
    if (!auth_user()) {
        flash_set('error', 'Войдите в систему.');
        redirect(url('login'));
    }
    auth_refresh_session_role();
}

/**
 * Подтянуть роль из БД (на случай старой сессии без role).
 */
function auth_refresh_session_role(): void
{
    global $pdo;
    if (empty($_SESSION['user']['id']) || !($pdo instanceof PDO)) {
        return;
    }
    try {
        $row = db_fetch($pdo, 'SELECT role, display_name, login FROM users WHERE id = ?', [(int) $_SESSION['user']['id']]);
    } catch (Throwable $e) {
        return;
    }
    if (!$row) {
        auth_logout();
        flash_set('error', 'Учётная запись не найдена. Войдите снова.');
        redirect(url('login'));
    }
    $role = $row['role'] ?? 'admin';
    if ($role !== 'admin' && $role !== 'chief_expert') {
        $role = 'admin';
    }
    $_SESSION['user']['role'] = $role;
    $_SESSION['user']['display_name'] = $row['display_name'];
    $_SESSION['user']['login'] = $row['login'];
}

function auth_is_admin(): bool
{
    $user = auth_user();
    if (!$user) {
        return false;
    }
    // Старая сессия без роли — считаем админом только после refresh; до него безопасный fallback по логину
    $role = $user['role'] ?? null;
    if ($role === null || $role === '') {
        return ($user['login'] ?? '') === 'admin';
    }
    return $role === 'admin';
}

function auth_is_chief(): bool
{
    $user = auth_user();
    return $user && ($user['role'] ?? '') === 'chief_expert';
}

function auth_require_admin(): void
{
    auth_check();
    if (!auth_is_admin()) {
        flash_set('error', 'Доступ только для администратора.');
        redirect(url('home'));
    }
}

function auth_login(PDO $pdo, string $login, string $password): bool
{
    $user = db_fetch($pdo, 'SELECT * FROM users WHERE login = ?', [$login]);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    $role = $user['role'] ?? 'admin';
    if ($role !== 'admin' && $role !== 'chief_expert') {
        $role = 'admin';
    }
    // Страховка: учётка admin всегда администратор
    if (($user['login'] ?? '') === 'admin') {
        $role = 'admin';
        if (($user['role'] ?? '') !== 'admin') {
            try {
                db_exec($pdo, "UPDATE users SET role = 'admin' WHERE id = ?", [(int) $user['id']]);
            } catch (Throwable $e) {
                // ignore
            }
        }
    }
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'login' => $user['login'],
        'display_name' => $user['display_name'],
        'role' => $role,
    ];
    unset($_SESSION['specialty_unlock']);
    return true;
}

function auth_logout(): void
{
    unset($_SESSION['user'], $_SESSION['specialty_unlock']);
}

function specialty_is_unlocked(int $specialtyId): bool
{
    return !empty($_SESSION['specialty_unlock'][$specialtyId]);
}

function specialty_unlock(int $specialtyId): void
{
    if (!isset($_SESSION['specialty_unlock']) || !is_array($_SESSION['specialty_unlock'])) {
        $_SESSION['specialty_unlock'] = [];
    }
    $_SESSION['specialty_unlock'][$specialtyId] = true;
}

function specialty_lock(int $specialtyId): void
{
    unset($_SESSION['specialty_unlock'][$specialtyId]);
}

/** Проверка, что компетенция доступна и разблокирована паролем (для POST). */
function specialty_assert_unlocked(PDO $pdo, int $specialtyId): void
{
    $specialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [$specialtyId]);
    if (!$specialty || !auth_can_access_specialty($specialty)) {
        flash_set('error', 'Нет доступа.');
        redirect(url('home'));
    }
    if (auth_is_admin()) {
        specialty_unlock($specialtyId);
        return;
    }
    if (!empty($specialty['chief_expert_id']) && !specialty_is_unlocked($specialtyId)) {
        flash_set('error', 'Сначала откройте компетенцию паролем главного эксперта.');
        redirect(url('specialty', ['id' => $specialtyId]));
    }
}

/**
 * Главный эксперт — только свои компетенции; админ — все.
 */
function auth_can_access_specialty(array $specialty): bool
{
    if (auth_is_admin()) {
        return true;
    }
    if (!auth_is_chief()) {
        return false;
    }
    $user = auth_user();
    return $user && (int) ($specialty['chief_expert_id'] ?? 0) === (int) $user['id'];
}

/**
 * Доступ к компетенции + пароль главного эксперта (модальное окно).
 *
 * @return array specialty
 */
function specialty_require_access(PDO $pdo, int $specialtyId, ?string $returnRoute = null, array $returnParams = []): array
{
    auth_check();

    $specialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [$specialtyId]);
    if (!$specialty) {
        flash_set('error', 'Компетенция не найдена.');
        redirect(url('home'));
    }

    if (!auth_can_access_specialty($specialty)) {
        flash_set('error', 'Нет доступа к этой компетенции.');
        redirect(url('home'));
    }

    // Администратор — полный доступ ко всем функциям компетенции без пароля
    if (auth_is_admin()) {
        specialty_unlock($specialtyId);
        return $specialty;
    }

    // Без назначенного главного эксперта главный эксперт войти не может
    if (empty($specialty['chief_expert_id'])) {
        flash_set('error', 'К компетенции не назначен главный эксперт.');
        redirect(url('home'));
    }

    if (specialty_is_unlocked($specialtyId)) {
        return $specialty;
    }

    $competition = db_fetch($pdo, 'SELECT * FROM competitions WHERE id = ?', [(int) $specialty['competition_id']]);
    $chief = db_fetch(
        $pdo,
        'SELECT id, login, display_name FROM users WHERE id = ? AND role = ?',
        [(int) $specialty['chief_expert_id'], 'chief_expert']
    );

    $route = $returnRoute ?? (preg_replace('/[^a-z0-9_]/', '', (string) ($_GET['r'] ?? 'specialty')) ?: 'specialty');
    $params = $returnParams;
    if ($params === []) {
        $params = $_GET;
        unset($params['r']);
    }

    render('specialty_unlock', [
        'pageTitle' => 'Доступ к компетенции',
        'specialty' => $specialty,
        'competition' => $competition,
        'chief' => $chief,
        'return_route' => $route,
        'return_params' => $params,
        'unlockError' => null,
    ]);
    exit;
}

function specialty_id_from_request(PDO $pdo): int
{
    $route = preg_replace('/[^a-z0-9_]/', '', (string) ($_GET['r'] ?? '')) ?: '';
    $id = get_int('id');

    if (get_int('specialty_id') > 0) {
        return get_int('specialty_id');
    }

    if (in_array($route, ['specialty', 'specialty_settings', 'scores', 'results', 'print_results'], true) && $id > 0) {
        return $id;
    }

    if ($route === 'contestant_edit' && $id > 0) {
        $row = db_fetch($pdo, 'SELECT specialty_id FROM contestants WHERE id = ?', [$id]);
        return $row ? (int) $row['specialty_id'] : 0;
    }

    if ($route === 'expert_edit' && $id > 0) {
        $row = db_fetch($pdo, 'SELECT specialty_id FROM experts WHERE id = ?', [$id]);
        return $row ? (int) $row['specialty_id'] : 0;
    }

    if ($route === 'module_edit' && $id > 0) {
        $row = db_fetch($pdo, 'SELECT specialty_id FROM modules WHERE id = ?', [$id]);
        return $row ? (int) $row['specialty_id'] : 0;
    }

    if ($route === 'criterion_edit') {
        if (get_int('module_id') > 0) {
            $row = db_fetch($pdo, 'SELECT specialty_id FROM modules WHERE id = ?', [get_int('module_id')]);
            return $row ? (int) $row['specialty_id'] : 0;
        }
        if ($id > 0) {
            $row = db_fetch(
                $pdo,
                'SELECT m.specialty_id FROM criteria c INNER JOIN modules m ON m.id = c.module_id WHERE c.id = ?',
                [$id]
            );
            return $row ? (int) $row['specialty_id'] : 0;
        }
    }

    if ($route === 'print_expert_blank' && get_int('module_id') > 0) {
        $row = db_fetch($pdo, 'SELECT specialty_id FROM modules WHERE id = ?', [get_int('module_id')]);
        return $row ? (int) $row['specialty_id'] : 0;
    }

    return 0;
}
