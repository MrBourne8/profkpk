<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (!isset($_SESSION['_flash'])) {
        return null;
    }
    $flash = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Неверный CSRF-токен. Обновите страницу и попробуйте снова.');
    }
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function post_string(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function post_int(string $key, int $default = 0): int
{
    return isset($_POST[$key]) ? (int) $_POST[$key] : $default;
}

function post_float(string $key, ?float $default = null): ?float
{
    if (!isset($_POST[$key]) || $_POST[$key] === '') {
        return $default;
    }
    return (float) str_replace(',', '.', (string) $_POST[$key]);
}

function get_int(string $key, int $default = 0): int
{
    return isset($_GET[$key]) ? (int) $_GET[$key] : $default;
}

function url(string $route, array $params = []): string
{
    global $config;
    $base = rtrim($config['base_path'] ?? '', '/');
    $query = array_merge(['r' => $route], $params);
    return $base . '/index.php?' . http_build_query($query);
}

function asset_url(string $path): string
{
    global $config;
    return rtrim($config['base_path'] ?? '', '/') . '/' . ltrim($path, '/');
}

/** Логотип движения «Профессионалы» для печатных бланков и протоколов. */
function print_brand_logo(): void
{
    $relative = 'assets/brand/professionals-logo.svg';
    if (!is_file(dirname(__DIR__) . '/' . $relative)) {
        return;
    }
    echo '<div class="print-brand">';
    echo '<img class="print-brand-logo" src="' . e(asset_url($relative)) . '" alt="Всероссийское чемпионатное движение «Профессионалы»">';
    echo '</div>';
}

function status_label(string $status): string
{
    return match ($status) {
        'draft' => 'Черновик',
        'active' => 'Активный',
        'finished' => 'Завершён',
        default => $status,
    };
}

/** Вносить оценки можно только при статусе конкурса «Активный». */
function competition_allows_scoring(?array $competition): bool
{
    return $competition && ($competition['status'] ?? '') === 'active';
}

function specialty_competition(PDO $pdo, int $specialtyId): ?array
{
    return db_fetch(
        $pdo,
        'SELECT c.* FROM competitions c
         INNER JOIN specialties s ON s.competition_id = c.id
         WHERE s.id = ?',
        [$specialtyId]
    );
}

/** Колонка рабочего места + жеребьёвка для уже существующих без РМ. */
function contestants_ensure_workplace_column(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    try {
        $col = $pdo->query("SHOW COLUMNS FROM contestants LIKE 'workplace'")->fetch();
        if (!$col) {
            $pdo->exec('ALTER TABLE contestants ADD COLUMN workplace INT UNSIGNED NULL AFTER group_name');
        }
        $idx = $pdo->query("SHOW INDEX FROM contestants WHERE Key_name = 'uq_contestant_workplace'")->fetch();
        if (!$idx) {
            $pdo->exec('ALTER TABLE contestants ADD UNIQUE KEY uq_contestant_workplace (specialty_id, workplace)');
        }
        $specs = db_fetch_all(
            $pdo,
            'SELECT DISTINCT specialty_id FROM contestants WHERE workplace IS NULL'
        );
        foreach ($specs as $row) {
            contestants_backfill_workplaces($pdo, (int) $row['specialty_id']);
        }
    } catch (Throwable $e) {
        // таблица ещё не создана (install)
    }
    $done = true;
}

/** Назначить рабочие места конкурсантам компетенции, у которых их ещё нет. */
function contestants_backfill_workplaces(PDO $pdo, int $specialtyId): void
{
    $rows = db_fetch_all(
        $pdo,
        'SELECT id FROM contestants WHERE specialty_id = ? AND workplace IS NULL ORDER BY sort_order, id',
        [$specialtyId]
    );
    foreach ($rows as $row) {
        $wp = contestant_draw_workplace($pdo, $specialtyId);
        db_exec($pdo, 'UPDATE contestants SET workplace = ? WHERE id = ?', [$wp, (int) $row['id']]);
    }
}

/**
 * Жеребьёвка: случайное свободное рабочее место в диапазоне 1…N
 * (N = текущее число занятых мест + 1).
 */
function contestant_draw_workplace(PDO $pdo, int $specialtyId): int
{
    $usedRows = db_fetch_all(
        $pdo,
        'SELECT workplace FROM contestants WHERE specialty_id = ? AND workplace IS NOT NULL',
        [$specialtyId]
    );
    $used = [];
    foreach ($usedRows as $row) {
        $used[(int) $row['workplace']] = true;
    }
    $poolSize = count($used) + 1;
    $free = [];
    for ($i = 1; $i <= $poolSize; $i++) {
        if (!isset($used[$i])) {
            $free[] = $i;
        }
    }
    if ($free === []) {
        return $poolSize;
    }
    return $free[random_int(0, count($free) - 1)];
}

function contestant_workplace_label(?array $contestant): string
{
    $wp = $contestant['workplace'] ?? null;
    if ($wp === null || $wp === '') {
        return '—';
    }
    return 'РМ ' . (int) $wp;
}

function format_points(?float $value): string
{
    if ($value === null) {
        return '—';
    }
    $formatted = number_format($value, 2, '.', '');
    return rtrim(rtrim($formatted, '0'), '.') ?: '0';
}

/**
 * Разбор допустимых значений критерия (кроме 0), максимум 5 штук, по возрастанию.
 *
 * @return list<float>
 */
function criterion_parse_allowed_values(null|string|array $raw): array
{
    if (is_array($raw)) {
        $decoded = $raw;
    } else {
        $decoded = json_decode((string) ($raw ?? '[]'), true);
    }
    if (!is_array($decoded)) {
        return [];
    }

    $values = [];
    foreach ($decoded as $item) {
        if ($item === '' || $item === null) {
            continue;
        }
        // Поддержка 0.5, 0,5 и лишних нулей вроде 00.5
        $raw = trim((string) $item);
        $raw = str_replace([' ', ','], ['', '.'], $raw);
        if ($raw === '' || !is_numeric($raw)) {
            continue;
        }
        $num = (float) $raw;
        // 0 уже всегда есть в списке оценок — здесь только значения > 0
        if ($num <= 0) {
            continue;
        }
        $key = format_points($num);
        $values[$key] = $num;
        if (count($values) >= 5) {
            break;
        }
    }
    $list = array_values($values);
    sort($list, SORT_NUMERIC);
    return $list;
}

/**
 * Полный список вариантов для выбора: всегда включает 0.
 *
 * @return list<float>
 */
function criterion_score_options(array $criterion): array
{
    $allowed = criterion_parse_allowed_values($criterion['allowed_values'] ?? []);
    return array_merge([0.0], $allowed);
}

function criterion_format_allowed_label(array $criterion): string
{
    $opts = criterion_score_options($criterion);
    return implode(', ', array_map(static fn($v) => format_points($v), $opts));
}

function criterion_is_allowed_score(array $criterion, float $points): bool
{
    foreach (criterion_score_options($criterion) as $opt) {
        if (abs($opt - $points) < 0.00001) {
            return true;
        }
    }
    return false;
}

function render(string $view, array $data = [], string $layout = 'layout'): void
{
    global $config, $pdo;
    extract($data, EXTR_SKIP);
    $flash = flash_get();
    $viewFile = dirname(__DIR__) . '/views/' . $view . '.php';
    if (!is_file($viewFile)) {
        http_response_code(404);
        echo 'Представление не найдено: ' . e($view);
        return;
    }
    ob_start();
    require $viewFile;
    $content = ob_get_clean();
    require dirname(__DIR__) . '/views/' . $layout . '.php';
}

function require_install_or_redirect(): void
{
    global $pdo;
    try {
        $pdo->query('SELECT 1 FROM users LIMIT 1');
    } catch (Throwable $e) {
        redirect('install.php');
    }
}
