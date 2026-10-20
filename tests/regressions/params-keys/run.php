<?php

declare(strict_types=1);

use yii2\extensions\mago\ServiceMap;
use yii2\extensions\mago\StubGenerator;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';

if (in_array('--with-key-helper', $argv, true)) {
    $binaryIndex = array_search('--mago-binary', $argv, true);
    $binary = $binaryIndex !== false
        ? $argv[$binaryIndex + 1] ?? ''
        : (getenv('MAGO_BINARY') ?: $root . '/vendor/bin/mago');
    runWithParamsKeys($root, $binary);
    exit(0);
}

$workspace = sys_get_temp_dir() . '/mago-params-keys-' . bin2hex(random_bytes(8));
mkdir($workspace);
$quote = static fn(string $path): string => json_encode($path, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
file_put_contents($workspace . '/Yii.php', StubGenerator::generate(new ServiceMap(__DIR__ . '/config.php')));
$configuration = [
    'php-version = "8.3"',
    '[source]',
    'paths = [' . $quote(__DIR__ . '/fixture.php') . ']',
    'includes = ['
        . $quote($root . '/vendor/yiisoft/yii2')
        . ', '
        . $quote($root . '/vendor/carthage-software/mago/composer/src/functions.php')
        . ']',
    'patches = ["Yii.php"]',
    '[analyzer]',
    'find-unused-definitions = false',
    'find-unused-expressions = false',
    '[extension-hosts.yii2]',
    'command = ["php", ' . $quote(__DIR__ . '/worker.php') . ']',
];
file_put_contents($workspace . '/mago.toml', implode("\n", $configuration) . "\n");
$command = [
    getenv('MAGO_BINARY') ?: $root . '/vendor/bin/mago',
    '--workspace',
    $workspace,
    'analyze',
    '--reporting-format',
    'short',
    '--minimum-report-level',
    'warning',
    '--minimum-fail-level',
    'warning',
];
$process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes);
if (!is_resource($process)) {
    throw new RuntimeException('Cannot start Mago.');
}
$status = proc_close($process);
fwrite(STDOUT, 'Analysis-only patch retained at ' . $workspace . "/Yii.php\n");
exit($status);

/** Required green counterpart; the original 68 expectations and default red probe stay unchanged. */
function runWithParamsKeys(string $root, string $binary): void
{
    $workspace = sys_get_temp_dir() . '/mago-params-key-artifacts-' . bin2hex(random_bytes(8));
    mkdir($workspace);
    $quote = static fn(string $path): string => json_encode($path, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $artifacts = $root . '/tests/corpus/params-key-artifacts';
    $cases = [
        'params keys' => [__DIR__ . '/config.php', __DIR__ . '/fixture.php', true, 68],
        'ordinary keys (original API)' => [
            $artifacts . '/ordinary-config.php',
            $artifacts . '/ordinary.php',
            false,
            28,
        ],
        'extra bundle keys' => [$artifacts . '/bundle-config.php', $artifacts . '/bundle.php', true, 11],
    ];
    foreach ($cases as $name => [$config, $fixture, $withHelper, $expected]) {
        $directory = $workspace . '/' . ($withHelper ? 'bundle' : 'ordinary') . '-' . $expected;
        mkdir($directory);
        $services = new ServiceMap($config);
        $bundle = $withHelper
            ? StubGenerator::generateWithParamsKeys($services)
            : ['patch' => StubGenerator::generate($services)];
        $patch = $directory . '/Yii.php';
        $helper = $directory . '/ParamsKeys.php';
        file_put_contents($patch, $bundle['patch']);
        $includes = [
            $root . '/vendor/yiisoft/yii2',
            $root . '/vendor/carthage-software/mago/composer/src/functions.php',
        ];
        $analysisFiles = [$patch, $fixture];
        if (isset($bundle['keys'])) {
            file_put_contents($helper, $bundle['keys']);
            $includes[] = $helper;
            $analysisFiles[] = $helper;
        }
        $runtimeLog = $directory . '/worker-runtime.jsonl';
        $configuration = [
            'php-version = "8.3"',
            '[source]',
            'paths = [' . $quote($fixture) . ']',
            'includes = [' . implode(', ', array_map($quote, $includes)) . ']',
            'patches = [' . $quote($patch) . ']',
            '[analyzer]',
            'find-unused-definitions = false',
            'find-unused-expressions = false',
            'ignore = []',
            '[extension-hosts.yii2]',
            'command = ['
                . implode(', ', array_map($quote, [
                    PHP_BINARY,
                    $artifacts . '/worker.php',
                    $config,
                    $runtimeLog,
                    ...$analysisFiles,
                ]))
                . ']',
        ];
        file_put_contents($directory . '/mago.toml', implode("\n", $configuration) . "\n");
        $command = [
            $binary,
            '--workspace',
            $directory,
            '--config',
            $directory . '/mago.toml',
            '--colors',
            'never',
            'analyze',
            '--reporting-format',
            'json',
            '--minimum-report-level',
            'help',
            '--minimum-fail-level',
            'warning',
            '--skip-ignores',
            '--ignore-baseline',
        ];
        $process = proc_open($command, [STDIN, ['pipe', 'w'], ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Cannot start Mago for ' . $name);
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        file_put_contents($directory . '/report.json', $stdout);
        file_put_contents($directory . '/stderr.txt', $stderr);
        $report = json_decode($stdout, true, flags: JSON_THROW_ON_ERROR);
        $issues = $report['issues'];
        $confirmations = array_filter(
            $issues,
            static fn(array $issue): bool => $issue['code'] === 'type-confirmation' && $issue['level'] === 'Help',
        );
        if ($status !== 0 || count($confirmations) !== $expected || count($issues) !== $expected) {
            fwrite(STDERR, $stdout . $stderr);
            throw new RuntimeException(
                $name
                . ': expected exactly '
                . $expected
                . ' confirmations and no other diagnostics. See '
                . $directory,
            );
        }
        $records = is_file($runtimeLog) ? file($runtimeLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        if ($records === []) {
            throw new RuntimeException('Missing worker nonexecution evidence for ' . $name);
        }
        foreach ($records as $record) {
            $runtime = json_decode($record, true, flags: JSON_THROW_ON_ERROR);
            if (
                $runtime !== ['loadedAnalysisFiles' => [], 'loadedAnalysisClasses' => [], 'applicationStarted' => false]
            ) {
                throw new RuntimeException('Analysis files executed or application started in ' . $name);
            }
        }
        fwrite(STDOUT, $name . ': ' . $expected . '/' . $expected . " exact confirmations; no runtime execution: OK\n");
    }
    fwrite(STDOUT, 'Analysis-only artifacts retained at ' . $workspace . "\n");
}
