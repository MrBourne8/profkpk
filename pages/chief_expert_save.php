<?php

declare(strict_types=1);

auth_require_admin();
csrf_verify();

$id = post_int('id');
$login = post_string('login');
$displayName = post_string('display_name');
$password = (string) ($_POST['password'] ?? '');

if ($login === '' || $displayName === '') {
    flash_set('error', 'Укажите логин и ФИО.');
    redirect($id > 0 ? url('chief_expert_edit', ['id' => $id]) : url('chief_expert_edit'));
}

if (!preg_match('/^[a-zA-Z0-9._-]{3,64}$/', $login)) {
    flash_set('error', 'Логин: 3–64 символа (латиница, цифры, ._-).');
    redirect($id > 0 ? url('chief_expert_edit', ['id' => $id]) : url('chief_expert_edit'));
}

try {
    if ($id > 0) {
        $existing = db_fetch($pdo, "SELECT id FROM users WHERE id = ? AND role = 'chief_expert'", [$id]);
        if (!$existing) {
            flash_set('error', 'Главный эксперт не найден.');
            redirect(url('chief_experts'));
        }
        if ($password !== '') {
            db_exec(
                $pdo,
                'UPDATE users SET login = ?, display_name = ?, password_hash = ? WHERE id = ?',
                [$login, $displayName, password_hash($password, PASSWORD_DEFAULT), $id]
            );
        } else {
            db_exec(
                $pdo,
                'UPDATE users SET login = ?, display_name = ? WHERE id = ?',
                [$login, $displayName, $id]
            );
        }
        flash_set('success', 'Главный эксперт сохранён.');
    } else {
        if (strlen($password) < 4) {
            flash_set('error', 'Задайте пароль не короче 4 символов.');
            redirect(url('chief_expert_edit'));
        }
        db_exec(
            $pdo,
            "INSERT INTO users (login, password_hash, display_name, role) VALUES (?, ?, ?, 'chief_expert')",
            [$login, password_hash($password, PASSWORD_DEFAULT), $displayName]
        );
        flash_set('success', 'Главный эксперт добавлен.');
    }
} catch (PDOException $e) {
    flash_set('error', 'Не удалось сохранить: возможно, логин уже занят.');
    redirect($id > 0 ? url('chief_expert_edit', ['id' => $id]) : url('chief_expert_edit'));
}

redirect(url('chief_experts'));
