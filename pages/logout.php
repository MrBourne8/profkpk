<?php

declare(strict_types=1);

auth_logout();
flash_set('success', 'Вы вышли из системы.');
redirect(url('login'));
