<?php

declare(strict_types=1);

/**
 * Минимальный читатель .xlsx (без внешних библиотек).
 *
 * @return list<array{name:string,rows:list<list<mixed>>}>
 */
function xlsx_read(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException('Файл не найден.');
    }
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Для импорта Excel нужен PHP-модуль zip.');
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('Не удалось открыть файл Excel.');
    }

    try {
        $shared = xlsx_shared_strings($zip);
        $sheetFiles = xlsx_sheet_files($zip);
        $sheets = [];
        foreach ($sheetFiles as $sheet) {
            $xml = $zip->getFromName($sheet['path']);
            if ($xml === false) {
                continue;
            }
            $sheets[] = [
                'name' => $sheet['name'],
                'rows' => xlsx_parse_sheet($xml, $shared),
            ];
        }
        if ($sheets === []) {
            throw new RuntimeException('В файле нет листов с данными.');
        }
        return $sheets;
    } finally {
        $zip->close();
    }
}

function xlsx_load_xml(string $xml): ?SimpleXMLElement
{
    // Убираем пространства имён — иначе xpath/children в PHP ведут себя нестабильно.
    $xml = preg_replace('/xmlns(:[a-z0-9]+)?="[^"]*"/i', '', $xml) ?? $xml;
    $sx = @simplexml_load_string($xml);
    return $sx instanceof SimpleXMLElement ? $sx : null;
}

/**
 * @return list<string>
 */
function xlsx_shared_strings(ZipArchive $zip): array
{
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === false || $xml === '') {
        return [];
    }
    $sx = xlsx_load_xml($xml);
    if ($sx === null) {
        return [];
    }
    $out = [];
    foreach ($sx->si as $si) {
        $text = '';
        if (isset($si->t)) {
            $text .= (string) $si->t;
        }
        if (isset($si->r)) {
            foreach ($si->r as $run) {
                if (isset($run->t)) {
                    $text .= (string) $run->t;
                }
            }
        }
        $out[] = $text;
    }
    return $out;
}

/**
 * @return list<array{name:string,path:string}>
 */
function xlsx_sheet_files(ZipArchive $zip): array
{
    $wb = $zip->getFromName('xl/workbook.xml');
    $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($wb === false || $rels === false) {
        throw new RuntimeException('Повреждённый файл Excel.');
    }

    $targets = [];
    if (preg_match_all(
        '/Relationship[^>]*Id="([^"]+)"[^>]*Target="([^"]+)"|Relationship[^>]*Target="([^"]+)"[^>]*Id="([^"]+)"/i',
        $rels,
        $m,
        PREG_SET_ORDER
    )) {
        foreach ($m as $row) {
            $id = $row[1] !== '' ? $row[1] : $row[4];
            $target = str_replace('\\', '/', $row[2] !== '' ? $row[2] : $row[3]);
            if ($id === '' || $target === '' || !str_contains(strtolower($target), 'worksheet')) {
                continue;
            }
            if (str_starts_with($target, '/')) {
                $path = ltrim($target, '/');
                if (!str_starts_with($path, 'xl/')) {
                    $path = 'xl/' . $path;
                }
            } else {
                $path = 'xl/' . ltrim($target, './');
            }
            $path = preg_replace('#^xl/xl/#', 'xl/', $path) ?? $path;
            $targets[$id] = $path;
        }
    }

    $sheets = [];
    if (preg_match_all('/<sheet\b([^>]*)\/?>/i', $wb, $sheetMatches, PREG_SET_ORDER)) {
        foreach ($sheetMatches as $sm) {
            $attrs = $sm[1];
            $name = 'Лист';
            if (preg_match('/\bname="([^"]*)"/i', $attrs, $nm)) {
                $name = html_entity_decode($nm[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
            $rid = '';
            if (preg_match('/\br:id="([^"]+)"/i', $attrs, $rm) || preg_match('/\bid="(rId[^"]+)"/i', $attrs, $rm)) {
                $rid = $rm[1];
            }
            if ($rid === '' || !isset($targets[$rid])) {
                continue;
            }
            $sheets[] = [
                'name' => $name !== '' ? $name : 'Лист',
                'path' => $targets[$rid],
            ];
        }
    }

    if ($sheets === []) {
        // Запасной путь: все worksheet*.xml по порядку
        for ($i = 1; $i <= 40; $i++) {
            $path = 'xl/worksheets/sheet' . $i . '.xml';
            if ($zip->locateName($path) === false) {
                break;
            }
            $sheets[] = ['name' => 'Лист ' . $i, 'path' => $path];
        }
    }

    return $sheets;
}

/**
 * @param list<string> $shared
 * @return list<list<mixed>>
 */
function xlsx_parse_sheet(string $xml, array $shared): array
{
    $sx = xlsx_load_xml($xml);
    if ($sx === null || !isset($sx->sheetData)) {
        return [];
    }

    $rows = [];
    $maxCol = 0;
    foreach ($sx->sheetData->row as $row) {
        $rIdx = (int) ($row['r'] ?? 0);
        if ($rIdx <= 0) {
            $rIdx = count($rows) + 1;
        }
        while (count($rows) < $rIdx) {
            $rows[] = [];
        }
        $line = [];
        foreach ($row->c as $c) {
            $ref = (string) ($c['r'] ?? '');
            $col = xlsx_col_index($ref);
            if ($col < 0) {
                continue;
            }
            $maxCol = max($maxCol, $col);
            $line[$col] = xlsx_cell_value($c, $shared);
        }
        $rows[$rIdx - 1] = $line;
    }

    $normalized = [];
    foreach ($rows as $line) {
        $width = max($maxCol + 1, $line ? (max(array_keys($line)) + 1) : 0, 1);
        $flat = [];
        for ($i = 0; $i < $width; $i++) {
            $flat[$i] = $line[$i] ?? null;
        }
        $normalized[] = $flat;
    }
    return $normalized;
}

function xlsx_col_index(string $ref): int
{
    if (!preg_match('/^([A-Z]+)/i', $ref, $m)) {
        return -1;
    }
    $letters = strtoupper($m[1]);
    $n = 0;
    for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
        $n = $n * 26 + (ord($letters[$i]) - 64);
    }
    return $n - 1;
}

/**
 * @param list<string> $shared
 */
function xlsx_cell_value(SimpleXMLElement $c, array $shared): mixed
{
    $type = (string) ($c['t'] ?? '');
    if ($type === 's') {
        $idx = (int) ($c->v ?? -1);
        return $shared[$idx] ?? '';
    }
    if ($type === 'inlineStr') {
        $text = '';
        if (isset($c->is->t)) {
            $text .= (string) $c->is->t;
        }
        if (isset($c->is->r)) {
            foreach ($c->is->r as $run) {
                if (isset($run->t)) {
                    $text .= (string) $run->t;
                }
            }
        }
        return $text;
    }
    if ($type === 'b') {
        return ((string) ($c->v ?? '0')) === '1';
    }
    $raw = (string) ($c->v ?? '');
    if ($raw === '') {
        return null;
    }
    if (is_numeric($raw)) {
        $num = (float) $raw;
        if (abs($num - (int) $num) < 0.0000001) {
            return (int) $num;
        }
        return $num;
    }
    return $raw;
}
