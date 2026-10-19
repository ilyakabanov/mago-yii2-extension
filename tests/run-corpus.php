<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$binary = getenv('MAGO_BINARY') ?: $root . '/vendor/bin/mago';
$checks = [
    'worker registration' => [$binary, 'extension', 'validate'],
    'inference' => [$binary, 'analyze', '--reporting-format', 'short', '--minimum-report-level', 'warning'],
    'static application stub' => [$binary, '--config', 'static-stubs.toml', 'analyze', '--reporting-format', 'short', '--minimum-report-level', 'warning'],
];
foreach ($checks as $name => $command) {
    $process = proc_open($command, [STDIN, ['pipe', 'w'], ['pipe', 'w']], $pipes, __DIR__ . '/corpus');
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot start Mago for ' . $name);
    }
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    if ($status !== 0) {
        fwrite(STDERR, $output . $errors);
        exit($status);
    }
    fwrite(STDOUT, $name . ": OK\n");
}
