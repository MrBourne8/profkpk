<?php

declare(strict_types=1);

require dirname(__DIR__) . '/lib/bootstrap.php';

foreach ($pdo->query('SELECT id, login, display_name, role FROM users') as $u) {
    echo $u['id'] . "\t" . $u['login'] . "\t" . $u['display_name'] . "\t" . $u['role'] . PHP_EOL;
}

$pdo->exec("UPDATE users SET role = 'admin' WHERE login = 'admin'");
echo "Forced admin role for login=admin\n";
