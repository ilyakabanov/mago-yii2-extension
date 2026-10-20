<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use yii2\extensions\mago\ServiceMap;
use yii2\extensions\mago\StubGenerator;

final class StubGeneratorCliTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir() . '/mago-stubs-cli-' . bin2hex(random_bytes(8));
        mkdir($this->workspace);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->workspace . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->workspace);
    }

    /** @return array{int, string, string} */
    private function runCli(array $arguments): array
    {
        $process = proc_open(
            [PHP_BINARY, dirname(__DIR__, 2) . '/bin/mago-yii2-stubs', ...$arguments],
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
            $this->workspace,
        );
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $stdout, $stderr];
    }

    public function testExistingOneFileModeIsUnchanged(): void
    {
        $config = __DIR__ . '/../corpus/config.php';
        [$status, $stdout, $stderr] = $this->runCli([$config, 'Yii.php']);
        self::assertSame(0, $status, $stderr);
        self::assertSame('', $stderr);
        self::assertSame("Generated analysis stub: Yii.php\n", $stdout);
        self::assertSame(
            StubGenerator::generate(new ServiceMap($config)),
            file_get_contents($this->workspace . '/Yii.php'),
        );
    }

    public function testOptionalCompanionModeWritesBothAnalysisArtifacts(): void
    {
        $config = __DIR__ . '/../regressions/params-keys/config.php';
        [$status, $stdout, $stderr] = $this->runCli([$config, 'Yii.php', 'ParamsKeys.php']);
        self::assertSame(0, $status, $stderr);
        self::assertSame('', $stderr);
        $bundle = StubGenerator::generateWithParamsKeys(new ServiceMap($config));
        self::assertSame($bundle['patch'], file_get_contents($this->workspace . '/Yii.php'));
        self::assertSame($bundle['keys'], file_get_contents($this->workspace . '/ParamsKeys.php'));
        self::assertStringContainsString('source.includes', $stdout);
        self::assertStringContainsString('source.patches', $stdout);
        self::assertStringContainsString('never require either at runtime', $stdout);
    }

    public static function badArguments(): iterable
    {
        yield 'none' => [[]];
        yield 'only config' => [['config.php']];
        yield 'too many' => [['config.php', 'Yii.php', 'Keys.php', 'extra']];
    }

    #[DataProvider('badArguments')]
    public function testBadArgumentsFailWithoutWriting(array $arguments): void
    {
        [$status, $stdout, $stderr] = $this->runCli($arguments);
        self::assertSame(1, $status);
        self::assertSame('', $stdout);
        self::assertStringContainsString('Usage:', $stderr);
        self::assertSame([], glob($this->workspace . '/*'));
    }

    public static function collisions(): iterable
    {
        yield 'identical' => ['identical'];
        yield 'relative alias' => ['relative'];
        yield 'symlink' => ['symlink'];
        yield 'symlink directory' => ['directory'];
        yield 'hardlink' => ['hardlink'];
    }

    #[DataProvider('collisions')]
    public function testCollisionDoesNotOverwriteEitherArtifact(string $kind): void
    {
        $patch = $this->workspace . '/Yii.php';
        file_put_contents($patch, 'preserve existing output');
        $helper = match ($kind) {
            'identical' => $patch,
            'relative' => $this->workspace . '/./Yii.php',
            'directory' => $this->workspace . '/alias/Yii.php',
            default => $this->workspace . '/ParamsKeys.php',
        };
        if ($kind === 'symlink') {
            symlink($patch, $helper);
        } elseif ($kind === 'directory') {
            symlink($this->workspace, $this->workspace . '/alias');
        } elseif ($kind === 'hardlink') {
            link($patch, $helper);
        }
        [$status, $stdout, $stderr] = $this->runCli([__DIR__ . '/../corpus/config.php', $patch, $helper]);
        self::assertSame(1, $status);
        self::assertSame('', $stdout);
        self::assertStringContainsString('destinations must be different', $stderr);
        self::assertSame('preserve existing output', file_get_contents($patch));
        self::assertSame('preserve existing output', file_get_contents($helper));
    }

    public function testNewRelativeAliasDestinationsCannotOverwriteOneAnother(): void
    {
        [$status, $stdout, $stderr] = $this->runCli([__DIR__ . '/../corpus/config.php', 'Yii.php', './Yii.php']);
        self::assertSame(1, $status);
        self::assertSame('', $stdout);
        self::assertStringContainsString('destinations must be different', $stderr);
        self::assertFileDoesNotExist($this->workspace . '/Yii.php');
    }

    public function testNewCaseAliasDestinationsRespectFilesystemIdentity(): void
    {
        $probe = $this->workspace . '/case-probe';
        file_put_contents($probe, '');
        $caseInsensitive = is_file($this->workspace . '/CASE-PROBE');
        unlink($probe);

        $config = __DIR__ . '/../corpus/config.php';
        [$status, $stdout, $stderr] = $this->runCli([$config, 'Yii.php', 'yii.php']);
        if ($caseInsensitive) {
            self::assertSame(1, $status);
            self::assertSame('', $stdout);
            self::assertStringContainsString('destinations must be different', $stderr);
            self::assertSame([], glob($this->workspace . '/*'));
            return;
        }

        self::assertSame(0, $status, $stderr);
        $bundle = StubGenerator::generateWithParamsKeys(new ServiceMap($config));
        self::assertSame($bundle['patch'], file_get_contents($this->workspace . '/Yii.php'));
        self::assertSame($bundle['keys'], file_get_contents($this->workspace . '/yii.php'));
    }

    public function testInvalidHelperDestinationDoesNotCreateNewPatch(): void
    {
        [$status, $stdout, $stderr] = $this->runCli([
            __DIR__ . '/../corpus/config.php',
            'Yii.php',
            'missing/Keys.php',
        ]);
        self::assertSame(1, $status);
        self::assertSame('', $stdout);
        self::assertStringContainsString('Cannot resolve output destination', $stderr);
        self::assertSame([], glob($this->workspace . '/*'));
    }

    public function testInvalidHelperDestinationIsValidatedBeforeWritingPatch(): void
    {
        $patch = $this->workspace . '/Yii.php';
        file_put_contents($patch, 'preserve existing output');
        [$status, $stdout, $stderr] = $this->runCli([
            __DIR__ . '/../corpus/config.php',
            $patch,
            $this->workspace . '/missing/Keys.php',
        ]);
        self::assertSame(1, $status);
        self::assertSame('', $stdout);
        self::assertStringContainsString('Cannot resolve output destination', $stderr);
        self::assertSame('preserve existing output', file_get_contents($patch));
    }

    public function testInvalidConfigWritesNeitherArtifact(): void
    {
        $config = $this->workspace . '/invalid.php';
        file_put_contents($config, "<?php return ['mago' => ['application_type' => 'bad */ name']];");
        [$status, $stdout, $stderr] = $this->runCli([$config, 'Yii.php', 'Keys.php']);
        self::assertSame(1, $status);
        self::assertSame('', $stdout);
        self::assertNotSame('', $stderr);
        self::assertFileDoesNotExist($this->workspace . '/Yii.php');
        self::assertFileDoesNotExist($this->workspace . '/Keys.php');
    }
}
