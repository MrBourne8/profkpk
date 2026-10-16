<?php

declare(strict_types=1);

auth_check();

// Итоги совмещены с рейтингом компетенции
redirect(url('specialty', ['id' => get_int('id')]));
