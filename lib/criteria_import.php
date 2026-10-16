<?php

declare(strict_types=1);

require_once __DIR__ . '/xlsx.php';

/**
 * Импорт критериев из Excel (форматы «плоский бланк» и «приложение по модулям»).
 *
 * @return array{modules:int,criteria:int,warnings:list<string>}
 */
function criteria_import_from_upload(PDO $pdo, int $specialtyId, string $tmpPath, string $originalName = ''): array
{
    $sheets = xlsx_read($tmpPath);
    $parsed = criteria_import_detect_and_parse($sheets, $originalName);
    if ($parsed['modules'] === []) {
        throw new RuntimeException('В файле не найдено аспектов для импорта.');
    }
    return criteria_import_persist($pdo, $specialtyId, $parsed);
}

/**
 * @param list<array{name:string,rows:list<list<mixed>>}> $sheets
 * @return array{modules:list<array{title:string,description:?string,criteria:list<array{title:string,description:?string,allowed:list<float>}>}>,warnings:list<string>}
 */
function criteria_import_detect_and_parse(array $sheets, string $originalName = ''): array
{
    if (criteria_import_looks_like_appendix($sheets)) {
        return criteria_import_parse_appendix($sheets);
    }
    return criteria_import_parse_flat($sheets[0]['rows'] ?? [], $sheets[0]['name'] ?? 'Критерии', $originalName);
}

/**
 * @param list<array{name:string,rows:list<list<mixed>>}> $sheets
 */
function criteria_import_looks_like_appendix(array $sheets): bool
{
    $hits = 0;
    foreach ($sheets as $sheet) {
        foreach ($sheet['rows'] as $row) {
            $mark = criteria_import_cell_str($row[2] ?? null);
            if ($mark === 'И' || $mark === 'С' || $mark === 'I' || $mark === 'C') {
                $hits++;
                if ($hits >= 3) {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * @param list<list<mixed>> $rows
 * @return array{modules:list<array{title:string,description:?string,criteria:list<array{title:string,description:?string,allowed:list<float>}>}>,warnings:list<string>}
 */
function criteria_import_parse_flat(array $rows, string $sheetName, string $originalName = ''): array
{
    $warnings = [];
    $moduleTitle = criteria_import_guess_flat_module_title($rows, $sheetName, $originalName);
    $criteria = [];
    $current = null;

    foreach ($rows as $row) {
        $num = $row[0] ?? null;
        $title = criteria_import_cell_str($row[1] ?? null);
        $extra = criteria_import_cell_str($row[2] ?? null);
        $score = criteria_import_cell_float($row[3] ?? null);

        // Строка шкалы: нет названия, есть расшифровка уровня и балл
        if ($title === '' && $extra !== '' && $score !== null) {
            if ($current === null) {
                continue;
            }
            $current['levels'][] = [
                'points' => $score,
                'text' => $extra,
            ];
            continue;
        }

        if ($title === '') {
            continue;
        }

        // Заголовки таблицы / служебные строки (не названия аспектов)
        $titleLower = mb_strtolower($title);
        $isHeader =
            $titleLower === 'описание аспекта'
            || str_starts_with($titleLower, 'описание аспекта')
            || str_starts_with($titleLower, 'конкурсант:')
            || str_starts_with($titleLower, 'конкурс профессионального');
        if ($isHeader) {
            continue;
        }
        // Строка только с подписью «Конкурсант: ____» без сути аспекта
        if (preg_match('/^конкурсант\s*:/u', $titleLower)) {
            continue;
        }

        if ($current !== null) {
            $final = criteria_import_finalize_criterion($current, $warnings);
            if ($final !== null) {
                $criteria[] = $final;
            }
        }

        $current = [
            'title' => $title,
            'description' => $extra !== '' ? $extra : null,
            'max' => $score,
            'levels' => [],
        ];
    }

    if ($current !== null) {
        $final = criteria_import_finalize_criterion($current, $warnings);
        if ($final !== null) {
            $criteria[] = $final;
        }
    }

    if ($criteria === []) {
        return ['modules' => [], 'warnings' => $warnings];
    }

    $sum = 0.0;
    foreach ($criteria as $cr) {
        $sum += max($cr['allowed']);
    }

    return [
        'modules' => [[
            'title' => $moduleTitle,
            'description' => null,
            'max_score' => round($sum, 2),
            'criteria' => $criteria,
        ]],
        'warnings' => $warnings,
    ];
}

/**
 * @param list<list<mixed>> $rows
 */
function criteria_import_guess_flat_module_title(array $rows, string $sheetName, string $originalName): string
{
    foreach ($rows as $row) {
        $text = criteria_import_cell_str($row[1] ?? null);
        if ($text === '') {
            continue;
        }
        $lower = mb_strtolower($text);
        if (str_contains($lower, 'конкурс профессионального') || str_contains($lower, 'конкурсант')) {
            continue;
        }
        if (str_contains($lower, 'описание аспекта')) {
            break;
        }
    }

    $base = pathinfo($originalName, PATHINFO_FILENAME);
    $base = trim((string) $base);
    if ($base !== '' && !preg_match('/^sheet\d*$/i', $base)) {
        return criteria_import_shorten($base, 255);
    }
    if ($sheetName !== '' && !preg_match('/^sheet\d*$/i', $sheetName)) {
        return criteria_import_shorten($sheetName, 255);
    }
    return 'Импорт критериев';
}

/**
 * Сумма баллов модуля в правых колонках (обычно колонка I).
 * Цвет заливки не используется — в разных документах он разный или отсутствует.
 *
 * @param list<mixed> $row
 */
function criteria_import_row_module_total(array $row): ?float
{
    // Предпочтительно колонка I (индекс 8) — именно там в шаблонах сумма модуля
    $candidates = [];
    foreach ([8, 9, 7, 6, 10, 11] as $idx) {
        $v = criteria_import_cell_float($row[$idx] ?? null);
        if ($v !== null && $v > 0) {
            $candidates[] = $v;
        }
    }
    if ($candidates === []) {
        return null;
    }
    // На строке модуля обычно одно крупное число (сумма); берём максимальное из правого блока
    return max($candidates);
}

/**
 * Строка-разделитель модуля.
 * Признаки (цвет заливки НЕ учитывается):
 * — буква модуля в A (А/Б/В…) и/или длинное название;
 * — нет маркера аспекта «И»/«С»;
 * — в правых колонках сумма баллов модуля.
 *
 * @param list<mixed> $row
 * @return array{letter:?string,title:string,total:?float}|null
 */
function criteria_import_module_header(array $row): ?array
{
    $colA = criteria_import_cell_str($row[0] ?? null);
    $colB = criteria_import_cell_str($row[1] ?? null);
    $mark = mb_strtoupper(criteria_import_cell_str($row[2] ?? null));
    $aspectTitle = criteria_import_cell_str($row[3] ?? null);

    // Аспектные строки «И»/«С» — не заголовок модуля
    if (in_array($mark, ['И', 'С', 'I', 'C'], true)) {
        return null;
    }
    if ($aspectTitle !== '') {
        return null;
    }

    $letter = preg_match('/^[А-ЯЁA-Z]$/u', $colA) ? $colA : null;
    $title = $colB;
    if ($title === '' && $letter === null && mb_strlen($colA) >= 8) {
        // Название модуля целиком в колонке A, без буквы
        $title = $colA;
    }
    if ($title === '' || mb_strlen($title) < 8) {
        return null;
    }

    // Номер подраздела (1, 2, 3…) — не модуль
    if ($letter === null && criteria_import_cell_float($row[0] ?? null) !== null) {
        $asNum = criteria_import_cell_float($row[0] ?? null);
        if ($asNum !== null && abs($asNum - round($asNum)) < 0.00001 && $asNum >= 1 && $asNum <= 50) {
            return null;
        }
    }

    $total = criteria_import_row_module_total($row);

    // Надёжный случай: буква + название (сумма желательна, но не обязательна)
    if ($letter !== null) {
        return ['letter' => $letter, 'title' => $title, 'total' => $total];
    }

    // Без буквы и без цвета: отделяем модуль по названию + сумме баллов модуля
    // (суммы модулей в шаблонах обычно ≥ 3–5, у аспекта при этом есть «И»/«С»)
    if ($total !== null && $total >= 3) {
        return ['letter' => null, 'title' => $title, 'total' => $total];
    }

    return null;
}

/**
 * @param list<array{name:string,rows:list<list<mixed>>}> $sheets
 * @return array{modules:list<array{title:string,description:?string,criteria:list<array{title:string,description:?string,allowed:list<float>}>}>,warnings:list<string>}
 */
function criteria_import_parse_appendix(array $sheets): array
{
    $warnings = [];
    $modules = [];

    foreach ($sheets as $sheet) {
        $sheetFallback = trim($sheet['name']) !== '' ? trim($sheet['name']) : 'Модуль';
        $moduleTitle = $sheetFallback;
        $moduleDescription = null;
        $moduleLetter = null;
        $moduleTotal = null;
        $criteria = [];
        $current = null;
        $sawModuleHeader = false;

        $flushCriterion = static function () use (&$current, &$criteria, &$warnings): void {
            if ($current === null) {
                return;
            }
            $final = criteria_import_finalize_criterion($current, $warnings);
            if ($final !== null) {
                $criteria[] = $final;
            }
            $current = null;
        };

        $flushModule = static function () use (
            &$criteria,
            &$modules,
            &$moduleTitle,
            &$moduleDescription,
            &$moduleLetter,
            &$moduleTotal,
            &$warnings,
            $sheetFallback,
            $flushCriterion
        ): void {
            $flushCriterion();
            if ($criteria === []) {
                return;
            }
            $title = $moduleTitle !== '' ? $moduleTitle : $sheetFallback;
            if ($moduleLetter !== null && !str_starts_with($title, $moduleLetter . '.')) {
                $title = $moduleLetter . '. ' . $title;
            }
            // Если сумма в шапке не указана — посчитать из аспектов
            $maxScore = $moduleTotal;
            if ($maxScore === null) {
                $sum = 0.0;
                foreach ($criteria as $cr) {
                    $sum += max($cr['allowed']);
                }
                $maxScore = round($sum, 2);
            }
            $modules[] = [
                'title' => criteria_import_shorten($title, 255),
                'description' => $moduleDescription,
                'max_score' => $maxScore,
                'criteria' => $criteria,
            ];
            $criteria = [];
            $moduleDescription = null;
            $moduleLetter = null;
            $moduleTotal = null;
        };

        foreach ($sheet['rows'] as $row) {
            $header = criteria_import_module_header($row);
            if ($header !== null) {
                // Новый модуль внутри листа (буква/название + сумма баллов; цвет не важен)
                if ($sawModuleHeader || $criteria !== [] || $current !== null) {
                    $flushModule();
                }
                $sawModuleHeader = true;
                $moduleLetter = $header['letter'];
                $moduleTitle = $header['title'];
                $moduleDescription = $header['title'];
                $moduleTotal = $header['total'];
                continue;
            }

            $mark = mb_strtoupper(criteria_import_cell_str($row[2] ?? null));
            $title = criteria_import_cell_str($row[3] ?? null);
            $levelIdx = criteria_import_cell_float($row[4] ?? null);
            $levelText = criteria_import_cell_str($row[5] ?? null);
            // Расшифровка / текст уровня — F (5), макс. балл — I (8), запасной — H (7)
            $max = criteria_import_cell_float($row[8] ?? null);
            if ($max === null) {
                $max = criteria_import_cell_float($row[7] ?? null);
            }

            // Уровень шкалы для предыдущего аспекта (подпункты 0..n)
            if ($mark === '' && $title === '' && $levelIdx !== null && $levelText !== '') {
                if ($current === null) {
                    continue;
                }
                $current['levels'][] = [
                    'index' => (int) round($levelIdx),
                    'points' => null,
                    'text' => $levelText,
                ];
                continue;
            }

            if (in_array($mark, ['И', 'С', 'I', 'C'], true) && $title !== '') {
                $flushCriterion();
                $description = criteria_import_cell_str($row[5] ?? null);
                $current = [
                    'title' => $title,
                    'description' => $description !== '' ? $description : null,
                    'max' => $max,
                    'levels' => [],
                    'kind' => $mark,
                ];
            }
        }

        $hadCriteriaBeforeFlush = $criteria !== [] || $current !== null;
        $modulesBefore = count($modules);
        $flushModule();
        if ($hadCriteriaBeforeFlush === false && count($modules) === $modulesBefore && !$sawModuleHeader) {
            $warnings[] = 'Лист «' . $sheetFallback . '»: аспекты не найдены, лист пропущен.';
        }
    }

    if ($modules === []) {
        $warnings[] = 'Не удалось выделить модули и аспекты в формате приложения.';
    }

    return ['modules' => $modules, 'warnings' => $warnings];
}

/**
 * Вытащить доли баллов из расшифровки аспекта (0,20 / 0.40 / «вычесть 0,15»).
 *
 * @return list<float>
 */
function criteria_import_extract_score_parts(string $text, ?float $max): array
{
    if ($text === '') {
        return [];
    }
    if (!preg_match_all('/(?<!\d)(\d+[.,]\d{1,2})(?!\d)/u', $text, $matches)) {
        return [];
    }
    $parts = [];
    foreach ($matches[1] as $raw) {
        $v = criteria_import_cell_float($raw);
        if ($v === null || $v <= 0 || $v > 100) {
            continue;
        }
        // Не берём числа больше максимума аспекта (кроме погрешности)
        if ($max !== null && $v > $max + 0.001) {
            continue;
        }
        $parts[] = round($v, 2);
    }
    return $parts;
}

/**
 * Построить допустимые оценки из долей в тексте.
 * Если суммы долей ≈ max — все возможные частичные суммы (0.20+0.40+0.10 → 0.10…0.70).
 * Если в тексте «вычесть X» — шаг вычитания от max.
 *
 * @param list<float> $parts
 * @return list<float>
 */
function criteria_import_allowed_from_parts(array $parts, float $max, string $description): array
{
    if ($max <= 0) {
        return [];
    }
    $parts = array_values(array_filter($parts, static fn(float $p): bool => $p > 0 && $p <= $max + 0.001));
    if ($parts === []) {
        return [round($max, 2)];
    }

    $isPenalty = (bool) preg_match('/вычесть/iu', $description);
    if ($isPenalty) {
        $step = min($parts);
        if ($step <= 0) {
            return [round($max, 2)];
        }
        $allowed = [];
        for ($v = $step; $v < $max - 0.0001; $v += $step) {
            $allowed[] = round($v, 2);
            if (count($allowed) > 20) {
                break;
            }
        }
        $allowed[] = round($max, 2);
        return $allowed;
    }

    $sumParts = round(array_sum($parts), 2);
    // Доли складываются в максимум → частичные суммы как варианты оценки
    if (abs($sumParts - $max) <= 0.021 || abs($sumParts - $max) / max($max, 0.01) <= 0.06) {
        $sums = [0.0];
        foreach ($parts as $p) {
            $next = $sums;
            foreach ($sums as $s) {
                $next[] = round($s + $p, 2);
            }
            $sums = array_values(array_unique($next));
        }
        sort($sums, SORT_NUMERIC);
        $allowed = [];
        foreach ($sums as $s) {
            if ($s > 0 && $s <= $max + 0.001) {
                $allowed[] = round((float) $s, 2);
            }
        }
        $allowed[] = round($max, 2);
        return $allowed;
    }

    // Иначе — сами указанные значения + максимум
    $allowed = $parts;
    $allowed[] = round($max, 2);
    return $allowed;
}

/**
 * @param array{title:string,description:?string,max:?float,levels:list<array{points:?float,index?:int,text:string}>} $raw
 * @param list<string> $warnings
 * @return array{title:string,description:?string,allowed:list<float>}|null
 */
function criteria_import_finalize_criterion(array $raw, array &$warnings): ?array
{
    $title = criteria_import_shorten(trim($raw['title'] ?? ''), 255);
    if ($title === '') {
        return null;
    }

    $levels = $raw['levels'] ?? [];
    $max = $raw['max'] ?? null;
    $description = trim((string) ($raw['description'] ?? ''));
    $allowed = [];
    $scaleLines = [];

    if ($levels !== []) {
        $hasExplicitPoints = false;
        foreach ($levels as $lv) {
            if (isset($lv['points']) && $lv['points'] !== null) {
                $hasExplicitPoints = true;
                break;
            }
        }

        if ($hasExplicitPoints) {
            foreach ($levels as $lv) {
                $p = $lv['points'] ?? null;
                if ($p === null) {
                    continue;
                }
                $p = round((float) $p, 2);
                if ($p > 0) {
                    $allowed[] = $p;
                }
                $scaleLines[] = format_points($p) . ' — ' . $lv['text'];
            }
            if ($max !== null && $max > 0) {
                $allowed[] = round((float) $max, 2);
            }
        } else {
            $maxLevel = 0;
            foreach ($levels as $lv) {
                $maxLevel = max($maxLevel, (int) ($lv['index'] ?? 0));
            }
            $top = ($max !== null && $max > 0) ? (float) $max : (float) $maxLevel;
            if ($top <= 0) {
                $warnings[] = 'Аспект «' . $title . '»: не удалось определить баллы, пропущен.';
                return null;
            }
            foreach ($levels as $lv) {
                $idx = (int) ($lv['index'] ?? 0);
                $p = $maxLevel > 0 ? round($top * ($idx / $maxLevel), 2) : 0.0;
                if ($p > 0) {
                    $allowed[] = $p;
                }
                $scaleLines[] = format_points($p) . ' — ' . $lv['text'];
            }
            $allowed[] = round($top, 2);
        }
    } elseif ($max !== null && $max > 0) {
        $max = round((float) $max, 2);
        $parts = criteria_import_extract_score_parts($description, $max);
        $fromDesc = criteria_import_allowed_from_parts($parts, $max, $description);
        $allowed = $fromDesc !== [] ? $fromDesc : [$max];
    } else {
        $warnings[] = 'Аспект «' . $title . '»: нет баллов, пропущен.';
        return null;
    }

    // Сначала уникальные > 0, затем усечение с сохранением максимума (лимит 5)
    $uniq = [];
    foreach ($allowed as $v) {
        $v = round((float) $v, 2);
        if ($v > 0) {
            $uniq[format_points($v)] = $v;
        }
    }
    $allowed = array_values($uniq);
    sort($allowed, SORT_NUMERIC);

    if (count($allowed) > 5) {
        $warnings[] = 'Аспект «' . $title . '»: шкала урезана до 5 значений (лимит системы).';
        $maxVal = max($allowed);
        $rest = [];
        foreach ($allowed as $v) {
            if (abs($v - $maxVal) > 0.00001) {
                $rest[] = $v;
            }
        }
        $picked = [];
        if ($rest === []) {
            $picked = [$maxVal];
        } elseif (count($rest) <= 4) {
            $picked = $rest;
            $picked[] = $maxVal;
        } else {
            $step = (count($rest) - 1) / 3;
            for ($i = 0; $i < 4; $i++) {
                $picked[] = $rest[(int) round($i * $step)];
            }
            $picked[] = $maxVal;
        }
        $allowed = $picked;
        $uniq = [];
        foreach ($allowed as $v) {
            $uniq[format_points((float) $v)] = round((float) $v, 2);
        }
        $allowed = array_values($uniq);
        sort($allowed, SORT_NUMERIC);
    }

    $allowed = criterion_parse_allowed_values($allowed);
    if ($allowed === []) {
        $warnings[] = 'Аспект «' . $title . '»: после разбора не осталось допустимых баллов, пропущен.';
        return null;
    }

    if ($scaleLines !== []) {
        $scaleBlock = "Шкала:\n" . implode("\n", $scaleLines);
        $description = $description !== '' ? ($description . "\n\n" . $scaleBlock) : $scaleBlock;
    }

    return [
        'title' => $title,
        'description' => $description !== '' ? $description : null,
        'allowed' => $allowed,
    ];
}

/**
 * @param array{modules:list<array{title:string,description:?string,max_score?:?float,criteria:list<array{title:string,description:?string,allowed:list<float>}>}>,warnings:list<string>} $parsed
 * @return array{modules:int,criteria:int,warnings:list<string>}
 */
function criteria_import_persist(PDO $pdo, int $specialtyId, array $parsed): array
{
    $sortModules = (int) (db_fetch(
        $pdo,
        'SELECT COALESCE(MAX(sort_order), 0) AS m FROM modules WHERE specialty_id = ?',
        [$specialtyId]
    )['m'] ?? 0);

    $modulesCreated = 0;
    $criteriaCreated = 0;

    $pdo->beginTransaction();
    try {
        foreach ($parsed['modules'] as $moduleData) {
            $sortModules++;
            db_exec(
                $pdo,
                'INSERT INTO modules (specialty_id, title, description, max_score, sort_order) VALUES (?, ?, ?, ?, ?)',
                [
                    $specialtyId,
                    $moduleData['title'],
                    $moduleData['description'],
                    $moduleData['max_score'] ?? null,
                    $sortModules,
                ]
            );
            $moduleId = (int) $pdo->lastInsertId();
            $modulesCreated++;

            $sortCrit = 0;
            foreach ($moduleData['criteria'] as $cr) {
                $sortCrit++;
                $allowed = $cr['allowed'];
                $maxPoints = max($allowed);
                db_exec(
                    $pdo,
                    'INSERT INTO criteria (module_id, title, description, max_points, allowed_values, sort_order)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [
                        $moduleId,
                        $cr['title'],
                        $cr['description'],
                        $maxPoints,
                        json_encode($allowed, JSON_UNESCAPED_UNICODE),
                        $sortCrit,
                    ]
                );
                $criteriaCreated++;
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return [
        'modules' => $modulesCreated,
        'criteria' => $criteriaCreated,
        'warnings' => $parsed['warnings'],
    ];
}

function criteria_import_cell_str(mixed $value): string
{
    if ($value === null || $value === '') {
        return '';
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    return trim(preg_replace("/[ \t]+/u", ' ', str_replace(["\r\n", "\r"], "\n", (string) $value)) ?? (string) $value);
}

function criteria_import_cell_float(mixed $value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    if (is_int($value) || is_float($value)) {
        return (float) $value;
    }
    $raw = trim((string) $value);
    $raw = str_replace([' ', ','], ['', '.'], $raw);
    if ($raw === '' || !is_numeric($raw)) {
        return null;
    }
    return (float) $raw;
}

function criteria_import_shorten(string $text, int $max): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $max - 1)) . '…';
}
