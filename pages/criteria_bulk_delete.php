<?php

declare(strict_types=1);

auth_check();
csrf_verify();

$specialtyId = post_int('specialty_id');
specialty_assert_unlocked($pdo, $specialtyId);

$rawIds = $_POST['ids'] ?? [];
if (!is_array($rawIds)) {
    $rawIds = [];
}
$ids = [];
foreach ($rawIds as $raw) {
    $id = (int) $raw;
    if ($id > 0) {
        $ids[$id] = $id;
    }
}
$ids = array_values($ids);

if ($ids === []) {
    flash_set('error', 'Не выбран ни один аспект.');
    redirect(url('specialty_settings', ['id' => $specialtyId, 'tab' => 'criteria']));
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$params = array_merge($ids, [$specialtyId]);
$rows = db_fetch_all(
    $pdo,
    "SELECT c.id
     FROM criteria c
     INNER JOIN modules m ON m.id = c.module_id
     WHERE c.id IN ($placeholders) AND m.specialty_id = ?",
    $params
);
$allowedIds = array_map(static fn(array $r): int => (int) $r['id'], $rows);

if ($allowedIds === []) {
    flash_set('error', 'Выбранные аспекты не найдены.');
    redirect(url('specialty_settings', ['id' => $specialtyId, 'tab' => 'criteria']));
}

$delPlaceholders = implode(',', array_fill(0, count($allowedIds), '?'));
db_exec($pdo, "DELETE FROM criteria WHERE id IN ($delPlaceholders)", $allowedIds);

$count = count($allowedIds);
flash_set('success', $count === 1 ? 'Аспект удалён.' : ('Удалено аспектов: ' . $count . '.'));
redirect(url('specialty_settings', ['id' => $specialtyId, 'tab' => 'criteria']));
