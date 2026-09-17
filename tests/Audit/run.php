<?php

declare(strict_types=1);

use Ilyakabanov\MagoYii2\Tests\Audit\AuditBaseline;
use Ilyakabanov\MagoYii2\Tests\Audit\AuditEnvironment;
use Ilyakabanov\MagoYii2\Tests\Audit\AuditPaths;
use Ilyakabanov\MagoYii2\Tests\Audit\AuditReportPrinter;
use Ilyakabanov\MagoYii2\Tests\Audit\AuditRunner;
use Ilyakabanov\MagoYii2\Tests\Audit\ComposerInstaller;
use Ilyakabanov\MagoYii2\Tests\Audit\DiagnosticClassifier;
use Ilyakabanov\MagoYii2\Tests\Audit\FixtureCorpus;
use Ilyakabanov\MagoYii2\Tests\Audit\MagoClient;
use Ilyakabanov\MagoYii2\Tests\Audit\PhpcsOracle;
use Ilyakabanov\MagoYii2\Tests\Audit\ProcessRunner;
use Ilyakabanov\MagoYii2\Tests\Audit\SniffAuditor;

require __DIR__ . '/ProcessResult.php';
require __DIR__ . '/ProcessRunner.php';
require __DIR__ . '/AuditPaths.php';
require __DIR__ . '/ComposerInstaller.php';
require __DIR__ . '/AuditEnvironment.php';
require __DIR__ . '/PhpcsOracle.php';
require __DIR__ . '/FormatterRun.php';
require __DIR__ . '/FormatterRunFactory.php';
require __DIR__ . '/MagoClient.php';
require __DIR__ . '/FixtureFinder.php';
require __DIR__ . '/FixtureRanker.php';
require __DIR__ . '/FixtureSelector.php';
require __DIR__ . '/CaseWorkspace.php';
require __DIR__ . '/FixtureCorpus.php';
require __DIR__ . '/DiagnosticClassifier.php';
require __DIR__ . '/SniffAuditor.php';
require __DIR__ . '/AuditRunner.php';
require __DIR__ . '/AuditBaseline.php';
require __DIR__ . '/AuditReportPrinter.php';

try {
    $update = $argv === [$argv[0], '--update'];
    if (!$update && count($argv) !== 1) {
        throw new InvalidArgumentException('Usage: php tests/Audit/run.php [--update]');
    }

    /** @var array{versions: array{phpcs: string, yii2-coding-standards: string, mago: string}, diagnostic-rules: array<string, list<string>>, preferred-fixtures: array<string, list<string>>, sniffs: array<string, list<string>>} $manifest */
    $manifest = require __DIR__ . '/manifest.php';
    $paths = new AuditPaths(
        projectRoot: dirname(__DIR__, levels: 2),
        cacheDirectory: '/tmp/mago-yii2-differential-audit',
    );
    $processes = new ProcessRunner();
    $environment = new AuditEnvironment(
        paths: $paths,
        versions: $manifest['versions'],
        processes: $processes,
        composer: new ComposerInstaller($processes),
    );

    fwrite(stream: STDERR, data: "Preparing pinned audit tools...\n");
    $environment->prepare();
    $fixtures = new FixtureCorpus(
        projectRoot: $paths->projectRoot,
        standardsRoot: $paths->phpcsStandardsSource,
        casesDirectory: $paths->casesDirectory,
        preferredFixtures: $manifest['preferred-fixtures'],
    );
    $phpcs = new PhpcsOracle($paths, $processes);
    $runner = new AuditRunner(
        versions: $manifest['versions'],
        sniffs: $manifest['sniffs'],
        phpcs: $phpcs,
        sniffAuditor: new SniffAuditor(
            phpcs: $phpcs,
            mago: new MagoClient($paths, $processes),
            fixtures: $fixtures,
            classifier: new DiagnosticClassifier(),
            diagnosticRules: $manifest['diagnostic-rules'],
        ),
        fixtures: $fixtures,
    );
    $report = $runner->run();
    $baseline = new AuditBaseline(
        baselinePath: __DIR__ . '/baseline.json',
        actualPath: $paths->cacheDirectory . '/actual.json',
    );
    if ($update) {
        $baseline->update($report);
    }
    if (!$update) {
        $baseline->assertMatches($report);
    }

    (new AuditReportPrinter())->display($report['scope'], $report['summary']);
} catch (Throwable $exception) {
    fwrite(stream: STDERR, data: 'Audit failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
