<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use PHPUnit\Framework\TestCase;

final class ParamsKeyRunnerTest extends TestCase
{
    public function testForwardedRelativeBinaryRunsFromEntryDirectory(): void
    {
        $process = proc_open(
            [
                PHP_BINARY,
                dirname(__DIR__) . '/regressions/params-keys/run.php',
                '--with-key-helper',
                '--mago-binary',
                '../../vendor/bin/mago',
            ],
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
            dirname(__DIR__) . '/corpus',
        );
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        self::assertSame(0, proc_close($process), $stdout . $stderr);
        self::assertStringContainsString('68/68 exact confirmations', $stdout);
        self::assertStringContainsString('28/28 exact confirmations', $stdout);
        self::assertStringContainsString('11/11 exact confirmations', $stdout);
    }
}
