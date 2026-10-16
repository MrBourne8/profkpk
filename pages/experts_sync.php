<?php

declare(strict_types=1);

auth_check();
csrf_verify();

$specialtyId = post_int('specialty_id');
specialty_assert_unlocked($pdo, $specialtyId);
$specialty = db_fetch($pdo, 'SELECT * FROM specialties WHERE id = ?', [$specialtyId]);
if (!$specialty) {
    flash_set('error', 'Компетенция не найдена.');
    redirect(url('home'));
}

$names = $_POST['names'] ?? [];
if (!is_array($names)) {
    $names = [];
}

$count = (int) $specialty['experts_count'];
$pdo->beginTransaction();
try {
    for ($i = 1; $i <= $count; $i++) {
        $name = trim((string) ($names[$i] ?? ('Эксперт ' . $i)));
        if ($name === '') {
            $name = 'Эксперт ' . $i;
        }
        $existing = db_fetch(
            $pdo,
            'SELECT id FROM experts WHERE specialty_id = ? AND number = ?',
            [$specialtyId, $i]
        );
        if ($existing) {
            db_exec($pdo, 'UPDATE experts SET full_name = ? WHERE id = ?', [$name, $existing['id']]);
        } else {
            db_exec(
                $pdo,
                'INSERT INTO experts (specialty_id, number, full_name) VALUES (?, ?, ?)',
                [$specialtyId, $i, $name]
            );
        }
    }
    // Удалить лишних экспертов сверх experts_count
    db_exec(
        $pdo,
        'DELETE FROM experts WHERE specialty_id = ? AND number > ?',
        [$specialtyId, $count]
    );
    $pdo->commit();
    flash_set('success', 'Список экспертов обновлён.');
} catch (Throwable $e) {
    $pdo->rollBack();
    flash_set('error', 'Ошибка сохранения экспертов: ' . $e->getMessage());
}

redirect(url('specialty_settings', ['id' => $specialtyId, 'tab' => 'experts']));
