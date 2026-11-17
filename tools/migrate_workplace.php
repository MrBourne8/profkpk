<?php

declare(strict_types=1);

require dirname(__DIR__) . '/lib/bootstrap.php';

contestants_ensure_workplace_column($pdo);
echo "Migration OK: workplace column ready.\n";
