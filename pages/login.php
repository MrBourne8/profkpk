<?php

declare(strict_types=1);

if (auth_user()) {
    redirect(url('home'));
}

if (request_method() === 'POST') {
    csrf_verify();
    $login = post_string('login');
    $password = (string) ($_POST['password'] ?? '');
    if (auth_login($pdo, $login, $password)) {
        flash_set('success', 'Добро пожаловать.');
        redirect(url('home'));
    }
    flash_set('error', 'Неверный логин или пароль.');
    redirect(url('login'));
}

render('login', ['pageTitle' => 'Вход'], 'layout');
