<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use yii2\extensions\mago\tests\Parity\RegressionChecker;

try {
    if (count($argv) !== 2 || !is_readable($argv[1])) {
        throw new RuntimeException('Usage: php tests/check-parity-regressions.php <runner-json-report>');
    }
    $actual = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
    $saved = json_decode(file_get_contents(__DIR__ . '/Parity/current-results.json'), true, flags: JSON_THROW_ON_ERROR);
    if (!is_array($actual) || !is_array($saved)) {
        throw new RuntimeException('Expected JSON report objects.');
    }
    $errors = (new RegressionChecker())->check($actual, $saved);
    $passed = $expected = $mismatches = $native = 0;
    foreach ($actual['reports'] as $report) {
        printf(
            "%s: %d/%d passed; %d mismatches; %d native errors\n",
            $report['profile'],
            $report['passed'],
            $report['expected'],
            count($report['mismatches']),
            count($report['analyzerErrors']),
        );
        $passed += $report['passed'];
        $expected += $report['expected'];
        $mismatches += count($report['mismatches']);
        $native += count($report['analyzerErrors']);
    }
    printf(
        "Full parity: %d/%d passed; %d remaining mismatches; %d native errors.\n",
        $passed,
        $expected,
        $mismatches,
        $native,
    );
    if ($errors !== []) {
        throw new RuntimeException(implode("\n", $errors));
    }
    echo "No regressions against the saved report; this does not mean full parity passes.\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Parity regression check failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
