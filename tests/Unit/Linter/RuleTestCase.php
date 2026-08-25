<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter;

use PHPUnit\Framework\TestCase;

use function array_column;
use function basename;
use function dirname;
use function escapeshellarg;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_string;
use function json_decode;
use function json_encode;
use function shell_exec;
use function sprintf;
use function trim;
use function uniqid;
use function unlink;

use const JSON_THROW_ON_ERROR;

/**
 * Base test case for Mago linter rules using separate .php fixture files.
 */
abstract class RuleTestCase extends TestCase
{
    private ?string $tempFile = null;

    abstract protected function getRuleCode(): string;

    protected function tearDown(): void
    {
        $this->cleanupTempFile();
        parent::tearDown();
    }

    protected function assertValidFixtureFile(string $filePath): void
    {
        $code = file_get_contents($filePath);
        self::assertIsString($code);

        $issues = $this->runLint($code);

        self::assertEmpty(
            $issues,
            'Expected 0 lint issues in ' . basename($filePath) . ', but found: '
                . json_encode($issues, flags: JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param list<string> $expectedMessages
     */
    protected function assertInvalidFixtureFile(string $filePath, array $expectedMessages): void
    {
        $code = file_get_contents($filePath);
        self::assertIsString($code);

        $issues = $this->runLint($code);
        $actualMessages = array_column($issues, 'message');

        self::assertSame($expectedMessages, $actualMessages);
    }

    protected function assertAutoFixFixtureFile(string $invalidFilePath, string $expectedFixedFilePath): void
    {
        $invalidCode = file_get_contents($invalidFilePath);
        $expectedFixedCode = file_get_contents($expectedFixedFilePath);
        self::assertIsString($invalidCode);
        self::assertIsString($expectedFixedCode);

        $tempFilePath = $this->createTempFile($invalidCode);
        $relativeName = basename($tempFilePath);
        $ruleCode = $this->getRuleCode();

        $this->executeMago("lint --only {$ruleCode} --fix src/{$relativeName}");

        $actualFixedCode = file_get_contents($tempFilePath);
        self::assertIsString($actualFixedCode);
        self::assertSame(trim($expectedFixedCode), trim($actualFixedCode));

        // Ensure fixed code produces no remaining issues
        $issues = $this->runLint($actualFixedCode);
        self::assertEmpty(
            $issues,
            'Expected 0 lint issues after fix, but found: ' . json_encode($issues, flags: JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return list<array{code: string, level: string, message: string, help: string}>
     */
    private function runLint(string $code): array
    {
        $filePath = $this->createTempFile($code);
        $relativeName = basename($filePath);
        $ruleCode = $this->getRuleCode();

        $output = $this->executeMago("lint --only {$ruleCode} --reporting-format json src/{$relativeName}");
        if ($output === null || trim($output) === '') {
            return [];
        }

        /** @var array{issues?: list<array{code: string, level: string, message: string, help: string}>} $data */
        $data = json_decode($output, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);

        return $data['issues'] ?? [];
    }

    private function createTempFile(string $code): string
    {
        $this->cleanupTempFile();

        $corpusSrc = dirname(__DIR__, levels: 2) . '/corpus/src';
        $fileName = 'fixture_' . uniqid(prefix: '', more_entropy: true) . '.php';
        $this->tempFile = $corpusSrc . '/' . $fileName;

        file_put_contents($this->tempFile, $code);

        return $this->tempFile;
    }

    private function cleanupTempFile(): void
    {
        if ($this->tempFile !== null && file_exists($this->tempFile)) {
            unlink($this->tempFile);
            $this->tempFile = null;
        }
    }

    private function executeMago(string $arguments): ?string
    {
        $magoBin = dirname(__DIR__, levels: 3) . '/vendor/bin/mago';
        $corpusDir = dirname(__DIR__, levels: 2) . '/corpus';

        $command = sprintf(
            '%s --workspace %s %s 2>/dev/null',
            escapeshellarg($magoBin),
            escapeshellarg($corpusDir),
            $arguments,
        );

        $output = shell_exec($command);

        return is_string($output) ? $output : null;
    }
}
