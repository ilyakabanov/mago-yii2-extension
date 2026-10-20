<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use yii2\extensions\mago\tests\Parity\RegressionChecker;

final class ParityRegressionCheckerTest extends TestCase
{
    public function testUnchangedNestedReportIgnoresOrderAndHostPaths(): void
    {
        $saved = $this->snapshot();
        $actual = $saved;
        foreach ($actual['reports'] as &$report) {
            foreach (['mismatches', 'analyzerErrors'] as $field) {
                foreach ($report[$field] as &$issue) {
                    $primary = [
                        'kind' => 'Primary',
                        'span' => [
                            'file_id' => ['path' => '/different/host/tests/' . $issue['file']],
                            'start' => ['line' => $issue['line']],
                        ],
                    ];
                    unset($issue['file'], $issue['line']);
                    $issue['annotations'] = [['kind' => 'Secondary'], $primary];
                }
                unset($issue);
                $report[$field] = array_reverse($report[$field]);
            }
        }
        unset($report);
        $actual['reports'] = array_reverse($actual['reports']);

        self::assertSame([], (new RegressionChecker())->check($actual, $saved));
    }

    public function testUnchangedCompactReportIsAccepted(): void
    {
        $saved = $this->snapshot();

        self::assertSame([], (new RegressionChecker())->check($saved, $saved));
    }

    public function testCompleteParityWouldBeAccepted(): void
    {
        $saved = $this->snapshot();
        $actual = $saved;
        $actual['success'] = true;
        foreach ($actual['reports'] as &$report) {
            $report['passed'] = $report['expected'];
            $report['mismatches'] = $report['analyzerErrors'] = [];
            $report['exitCode'] = 0;
        }
        unset($report);

        self::assertSame([], (new RegressionChecker())->check($actual, $saved));
    }

    public function testImprovementsAreAccepted(): void
    {
        $saved = $this->snapshot();
        $actual = $saved;
        array_pop($actual['reports'][0]['mismatches']);
        ++$actual['reports'][0]['passed'];
        array_pop($actual['reports'][3]['analyzerErrors']);

        self::assertSame([], (new RegressionChecker())->check($actual, $saved));
    }

    #[DataProvider('regressions')]
    public function testNewOrChangedIssuesAreRejected(string $change, string $message): void
    {
        $saved = $this->snapshot();
        $actual = $saved;
        switch ($change) {
            case 'new':
                $issue = $actual['reports'][0]['mismatches'][0];
                $issue['line'] = 999;
                $actual['reports'][1]['mismatches'][] = $issue;
                --$actual['reports'][1]['passed'];
                $actual['reports'][1]['exitCode'] = 1;
                break;
            case 'swap':
                array_pop($actual['reports'][0]['mismatches']);
                ++$actual['reports'][0]['passed'];
                $actual['reports'][1]['mismatches'][] = $saved['reports'][0]['mismatches'][0];
                --$actual['reports'][1]['passed'];
                $actual['reports'][1]['exitCode'] = 1;
                break;
            case 'message':
                $actual['reports'][0]['mismatches'][0]['message'] .= ' Changed result.';
                break;
            case 'duplicate':
                $actual['reports'][0]['mismatches'][] = $actual['reports'][0]['mismatches'][0];
                --$actual['reports'][0]['passed'];
                break;
            case 'native':
                $actual['reports'][3]['analyzerErrors'][0]['message'] .= ' New native error.';
                break;
            case 'native-duplicate':
                $actual['reports'][3]['analyzerErrors'][] = $actual['reports'][3]['analyzerErrors'][0];
                break;
        }

        self::assertStringContainsString($message, implode("\n", (new RegressionChecker())->check($actual, $saved)));
    }

    public static function regressions(): iterable
    {
        yield 'brand new mismatch' => ['new', 'console mismatches'];
        yield 'fixed plus newly broken at same total' => ['swap', 'console mismatches'];
        yield 'worsened known result' => ['message', 'web mismatches'];
        yield 'diagnostic multiplicity' => ['duplicate', 'web mismatches'];
        yield 'unexpected native error' => ['native', 'params analyzerErrors'];
        yield 'native multiplicity' => ['native-duplicate', 'params analyzerErrors'];
    }

    #[DataProvider('invalidReports')]
    public function testInvalidReportsFailClosed(string $change): void
    {
        $saved = $this->snapshot();
        $actual = $saved;
        switch ($change) {
            case 'missing-profile':
                array_pop($actual['reports']);
                break;
            case 'duplicate-profile':
                $actual['reports'][] = $actual['reports'][0];
                break;
            case 'expected':
                ++$actual['reports'][0]['expected'];
                break;
            case 'changed-counts':
                ++$actual['reports'][0]['expected'];
                ++$actual['reports'][0]['comparisons'];
                ++$actual['reports'][0]['passed'];
                break;
            case 'comparisons':
                --$actual['reports'][0]['comparisons'];
                break;
            case 'passed':
                ++$actual['reports'][0]['passed'];
                break;
            case 'missing-comparisons':
                unset($actual['reports'][0]['comparisons']);
                break;
            case 'infrastructure':
                $actual['reports'][0]['infrastructureFailure'] = true;
                break;
            case 'status':
                $actual['reports'][0]['exitCode'] = 2;
                break;
            case 'missing-status':
                unset($actual['reports'][0]['exitCode']);
                break;
            case 'malformed-issue':
                $actual['reports'][0]['mismatches'][0]['line'] = '19';
                break;
            case 'malformed-list':
                $actual['reports'][0]['analyzerErrors'] = null;
                break;
            case 'missing-primary':
                unset($actual['reports'][0]['mismatches'][0]['file']);
                $actual['reports'][0]['mismatches'][0]['annotations'] = [['kind' => 'Secondary']];
                break;
            case 'success':
                $actual['success'] = true;
                break;
            case 'malformed-profile':
                $actual['reports'][0]['profile'] = [];
                break;
        }

        $this->expectException(UnexpectedValueException::class);
        (new RegressionChecker())->check($actual, $saved);
    }

    public static function invalidReports(): iterable
    {
        foreach ([
            'missing-profile',
            'duplicate-profile',
            'expected',
            'changed-counts',
            'comparisons',
            'passed',
            'missing-comparisons',
            'infrastructure',
            'status',
            'missing-status',
            'malformed-issue',
            'malformed-list',
            'missing-primary',
            'success',
            'malformed-profile',
        ] as $change) {
            yield $change => [$change];
        }
    }

    public function testCliRejectsInvalidJson(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'parity-invalid-');
        self::assertNotFalse($path);
        try {
            file_put_contents($path, '{invalid');
            $pipes = [];
            $process = proc_open(
                [PHP_BINARY, dirname(__DIR__) . '/check-parity-regressions.php', $path],
                [
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes,
            );
            self::assertIsResource($process);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(1, proc_close($process));
            self::assertStringContainsString('Parity regression check failed:', $stderr);
            self::assertSame('', $stdout);
        } finally {
            unlink($path);
        }
    }

    private function snapshot(): array
    {
        return json_decode(
            file_get_contents(dirname(__DIR__) . '/Parity/current-results.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
