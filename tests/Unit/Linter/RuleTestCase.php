<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Linter;

use PHPUnit\Framework\TestCase;

use function basename;
use function count;
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
use function strrpos;
use function substr;
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

    protected function assertValidFixtureFile(string $filePath, string $phpVersion = '8.1'): void
    {
        $code = file_get_contents($filePath);
        self::assertIsString($code);

        $issues = $this->runLint($code, $phpVersion);

        self::assertEmpty(
            $issues,
            'Expected 0 lint issues in ' . basename($filePath) . ', but found: '
                . json_encode($issues, flags: JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param list<array{message: string, line: int, column: int}> $expectedIssues
     */
    protected function assertInvalidFixtureFile(
        string $filePath,
        array $expectedIssues,
        string $phpVersion = '8.1',
    ): void {
        $code = file_get_contents($filePath);
        self::assertIsString($code);

        $issues = $this->runLint($code, $phpVersion);
        self::assertCount(count($expectedIssues), $issues);

        foreach ($expectedIssues as $index => $expectedIssue) {
            $issue = $issues[$index];
            $start = $issue['annotations'][0]['span']['start'];

            self::assertSame($this->getRuleCode(), $issue['code']);
            self::assertSame('Error', $issue['level']);
            self::assertSame($expectedIssue['message'], $issue['message']);
            self::assertSame($expectedIssue['line'], $start['line'] + 1);
            self::assertSame($expectedIssue['column'], $this->getColumn($code, $start['offset']));
            self::assertEmpty($issue['edits'] ?? []);
        }
    }

    protected function assertFixtureIsNotModifiedByFix(string $filePath, string $phpVersion = '8.1'): void
    {
        $code = file_get_contents($filePath);
        self::assertIsString($code);

        $tempFilePath = $this->createTempFile($code);
        $relativeName = basename($tempFilePath);
        $ruleCode = $this->getRuleCode();

        $this->executeMago("lint --only {$ruleCode} --fix src/{$relativeName}", $phpVersion);

        $actualCode = file_get_contents($tempFilePath);
        self::assertIsString($actualCode);
        self::assertSame($code, $actualCode);
    }

    /**
     * @return list<array{
     *     code: string,
     *     level: string,
     *     message: string,
     *     help: string,
     *     annotations: list<array{span: array{start: array{offset: int, line: int}}}>,
     *     edits?: list<mixed>,
     * }>
     */
    private function runLint(string $code, string $phpVersion): array
    {
        $filePath = $this->createTempFile($code);
        $relativeName = basename($filePath);
        $ruleCode = $this->getRuleCode();

        $output = $this->executeMago(
            "lint --only {$ruleCode} --reporting-format json src/{$relativeName}",
            $phpVersion,
        );
        if ($output === null || trim($output) === '') {
            return [];
        }

        /**
         * @var array{issues?: list<array{
         *     code: string,
         *     level: string,
         *     message: string,
         *     help: string,
         *     annotations: list<array{span: array{start: array{offset: int, line: int}}}>,
         *     edits?: list<mixed>,
         * }>} $data
         */
        $data = json_decode($output, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);

        return $data['issues'] ?? [];
    }

    private function getColumn(string $code, int $offset): int
    {
        $lineBreak = strrpos(substr($code, offset: 0, length: $offset), needle: "\n");
        $lineStart = $lineBreak === false ? 0 : $lineBreak + 1;

        return $offset - $lineStart + 1;
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

    private function executeMago(string $arguments, string $phpVersion = '8.1'): ?string
    {
        $magoBin = dirname(__DIR__, levels: 3) . '/vendor/bin/mago';
        $corpusDir = dirname(__DIR__, levels: 2) . '/corpus';

        $command = sprintf(
            '%s --workspace %s --php-version %s %s 2>/dev/null',
            escapeshellarg($magoBin),
            escapeshellarg($corpusDir),
            escapeshellarg($phpVersion),
            $arguments,
        );

        $output = shell_exec($command);

        return is_string($output) ? $output : null;
    }
}
