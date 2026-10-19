<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use yii2\extensions\mago\tests\Parity\FixtureBuilder;

if (in_array('--generate-patch', $argv, true)) {
    $root = dirname(__DIR__);
    $artifacts = (new FixtureBuilder())->build($root);
    echo "*** Begin Patch\n";
    foreach ($artifacts as $path => $contents) {
        if (is_file($root . '/' . $path)) {
            echo '*** Update File: ' . $root . '/' . $path . "\n@@\n";
            foreach (explode("\n", rtrim(file_get_contents($root . '/' . $path), "\n")) as $line) { echo '-' . $line . "\n"; }
        } else {
            echo '*** Add File: ' . $root . '/' . $path . "\n";
        }
        foreach (explode("\n", rtrim($contents, "\n")) as $line) { echo '+' . $line . "\n"; }
    }
    echo "*** End Patch\n";
    exit(0);
}

$manifest = json_decode(file_get_contents(__DIR__ . '/Parity/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
$selected = null;
foreach ($argv as $index => $argument) {
    if (str_starts_with($argument, '--profile=')) { $selected = substr($argument, strlen('--profile=')); }
    if ($argument === '--profile') { $selected = $argv[$index + 1] ?? throw new RuntimeException('--profile requires a value.'); }
}
$selfTest = in_array('--self-test', $argv, true);
$strictNative = in_array('--strict-native', $argv, true);
$projectPatches = in_array('--project-patches', $argv, true);
$profiles = $selfTest ? ['self-test'] : ($selected !== null ? [$selected] : ['web', 'console', 'custom', 'params', 'awkward', 'definition']);
$binary = getenv('MAGO_BINARY') ?: dirname(__DIR__) . '/vendor/bin/mago';
$failed = false;
$reports = [];
$jsonReport = in_array('--json', $argv, true);
foreach ($profiles as $profile) {
    $cases = array_values(array_filter($manifest['cases'], static fn (array $case): bool => $profile === 'header'
        ? str_ends_with($case['file'], '/HeaderCollectionDynamicMethodReturnType.php')
        : $case['profile'] === $profile));
    $paths = [];
    foreach ($cases as $case) { $paths[__DIR__ . '/Parity/fixtures/' . $case['profile'] . '/' . $case['file']] = true; }
    if ($selfTest) { $paths = [__DIR__ . '/Parity/self-test.php' => true]; }
    if ($paths === []) { throw new RuntimeException('No fixtures selected for profile: ' . $profile); }
    $configuration = $projectPatches && $profile === 'web' ? 'web-project-patches' : $profile;
    $command = [$binary, '--workspace', __DIR__, '--config', __DIR__ . '/Parity/config/' . $configuration . '.toml', 'analyze', ...array_keys($paths), '--minimum-report-level', 'note', '--reporting-format', 'json'];
    $pipes = [];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__), [...getenv(), 'YII2_PARITY_PROFILE' => $profile]);
    if (!is_resource($process)) { throw new RuntimeException('Could not start Mago.'); }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    try { $payload = json_decode($stdout, true, flags: JSON_THROW_ON_ERROR); }
    catch (JsonException $exception) { fwrite(STDERR, $stderr . $stdout); throw new RuntimeException('Mago did not return JSON.', previous: $exception); }
    $issues = $payload['issues'] ?? $payload;
    $passes = 0;
    $failures = [];
    $otherErrors = [];
    foreach ($issues as $issue) {
        if (!is_array($issue)) { continue; }
        $code = $issue['code'] ?? '';
        if ($code === 'yii2/parity/pass') { ++$passes; }
        elseif (str_starts_with($code, 'yii2/parity/')) { $failures[] = $issue; }
        elseif (strtolower((string) ($issue['level'] ?? '')) === 'error') { $otherErrors[] = $issue; }
    }
    $comparisons = $passes + count($failures);
    $expected = $selfTest ? 5 : count($cases);
    $infrastructureFailure = !in_array($status, [0, 1], true) || preg_match('/External analyzer provider failed|Orchestrator error|External plugin error|worker closed stdout/', $stderr) === 1;
    $ok = $selfTest ? ($passes === 3 && count($failures) === 2 && $comparisons === 5 && $otherErrors === [] && !$infrastructureFailure)
        : ($passes === $expected && $failures === [] && !$infrastructureFailure && (!$strictNative || ($otherErrors === [] && $status === 0)));
    $failed = $failed || !$ok;
    $reports[] = ['profile' => $profile, 'configuration' => $configuration, 'passed' => $passes, 'expected' => $expected, 'comparisons' => $comparisons, 'mismatches' => $failures, 'analyzerErrors' => $otherErrors, 'infrastructureFailure' => $infrastructureFailure, 'exitCode' => $status];
    if ($jsonReport) { continue; }
    printf("%s: %d/%d semantic comparisons passed; %d mismatches; %d analyzer errors%s\n", $profile, $passes, $expected, count($failures), count($otherErrors), $ok ? '' : ' [FAILED]');
    foreach (array_slice([...$failures, ...$otherErrors], 0, 8) as $issue) { printf("  %s: %s\n", $issue['code'] ?? 'unknown', $issue['message'] ?? json_encode($issue)); }
    if ($comparisons !== $expected) { printf("  Missing comparisons: expected %d, received %d.\n", $expected, $comparisons); }
    if (!$ok && $stderr !== '') { fwrite(STDERR, $stderr); }
}
if ($jsonReport) { echo json_encode(['success' => !$failed, 'reports' => $reports], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"; }
exit($failed ? 1 : 0);
