<?php

declare(strict_types=1);

require dirname(__DIR__) . '/lib/scoring.php';

$fail = 0;
function assert_eq($expected, $actual, string $label): void
{
    global $fail;
    if ($actual === null && $expected !== null) {
        echo "FAIL $label: expected $expected, got null\n";
        $fail++;
        return;
    }
    if (abs((float) $expected - (float) $actual) > 0.0001) {
        echo "FAIL $label: expected $expected, got $actual\n";
        $fail++;
    } else {
        echo "OK   $label\n";
    }
}

$module = module_score_for_contestant(
    [1, 2],
    [1 => 0.0, 2 => 5.0]
);
assert_eq(5.0, $module, 'consensus_sum');

$partial = module_score_for_contestant([1, 2, 3], [1 => 1.0, 2 => null]);
assert_eq(1.0, $partial, 'partial_criteria');

$empty = module_score_for_contestant([1], [1 => null]);
if ($empty !== null) {
    echo "FAIL empty: expected null\n";
    $fail++;
} else {
    echo "OK   empty\n";
}

exit($fail > 0 ? 1 : 0);
