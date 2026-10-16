<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/criteria_import.php';

auth_check();
csrf_verify();

$specialtyId = post_int('specialty_id');
specialty_assert_unlocked($pdo, $specialtyId);

if (empty($_FILES['criteria_file']) || !is_array($_FILES['criteria_file'])) {
    flash_set('error', 'Выберите файл Excel (.xlsx).');
    redirect(url('specialty_settings', ['id' => $specialtyId, 'tab' => 'criteria']));
}

$file = $_FILES['criteria_file'];
$error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($error !== UPLOAD_ERR_OK) {
    flash_set('error', 'Не удалось загрузить файл (код ' . $error . ').');
    redirect(url('specialty_settings', ['id' => $specialtyId, 'tab' => 'criteria']));
}

$tmp = (string) ($file['tmp_name'] ?? '');
$name = (string) ($file['name'] ?? 'criteria.xlsx');
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
if ($ext !== 'xlsx') {
    flash_set('error', 'Поддерживается только формат .xlsx (Excel 2007+).');
    redirect(url('specialty_settings', ['id' => $specialtyId, 'tab' => 'criteria']));
}

try {
    $result = criteria_import_from_upload($pdo, $specialtyId, $tmp, $name);
} catch (Throwable $e) {
    flash_set('error', 'Импорт не выполнен: ' . $e->getMessage());
    redirect(url('specialty_settings', ['id' => $specialtyId, 'tab' => 'criteria']));
}

$msg = 'Импорт завершён: модулей — ' . $result['modules'] . ', аспектов — ' . $result['criteria'] . '.';
if ($result['warnings']) {
    $shown = array_slice($result['warnings'], 0, 5);
    $msg .= ' Замечания: ' . implode(' ', $shown);
    if (count($result['warnings']) > 5) {
        $msg .= ' …ещё ' . (count($result['warnings']) - 5) . '.';
    }
}
flash_set('success', $msg);
redirect(url('specialty_settings', ['id' => $specialtyId, 'tab' => 'criteria']));
