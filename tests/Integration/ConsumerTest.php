<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ConsumerTest extends TestCase
{
    /** @var non-empty-string */
    private string $workspace;

    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir() . '/mago-yii2-consumer-' . bin2hex(random_bytes(8));
        mkdir($this->workspace);
        mkdir($this->workspace . '/src');
    }

    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->workspace, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir() && !$file->isLink()) {
                rmdir($file->getPathname());
                continue;
            }

            unlink($file->getPathname());
        }

        rmdir($this->workspace);
    }

    /** @return array<string, array{bool}> */
    public static function installationModes(): array
    {
        return ['symlink' => [true], 'mirror' => [false]];
    }

    #[DataProvider('installationModes')]
    public function testComposerConsumerCanLoadThePreset(bool $symlink): void
    {
        $this->installPackage($symlink);

        $output = $this->executeCommand([PHP_BINARY, 'vendor/bin/mago', 'extension', 'validate']);

        self::assertStringContainsString('Validated 1 extension(s) from 1 host(s).', $output);

        $this->executeCommand([PHP_BINARY, 'vendor/bin/mago', 'lint']);
        $this->executeCommand([PHP_BINARY, 'vendor/bin/mago', 'format', '--check']);

        $this->copyIntegrationFixture('Linter/PrivateProperty.php', 'src/PrivateProperty.php');
        $output = $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'lint',
            '--only',
            'yii2/private-property-underscore',
            'src/PrivateProperty.php',
        ], expectedExit: 1);
        self::assertStringContainsString('yii2/private-property-underscore', $output);
    }

    public function testProjectCanOverrideSharedSettings(): void
    {
        $this->installPackage();
        $this->copyIntegrationFixture('Formatter/Input.php', 'src/Input.php');
        $this->executeCommand([PHP_BINARY, 'vendor/bin/mago', 'format', 'src/Input.php']);
        self::assertFileEquals(
            __DIR__ . '/Fixtures/Formatter/ExpectedSingleQuotes.php',
            $this->workspace . '/src/Input.php',
        );

        $this->copyIntegrationFixture('Linter/ArrayStyle.php', 'src/ArrayStyle.php');
        $output = $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'lint',
            '--only',
            'array-style',
            'src/ArrayStyle.php',
        ], expectedExit: 1);
        self::assertStringContainsString('array-style', $output);

        $output = $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            '--config',
            'mago-overrides.toml',
            'lint',
            '--only',
            'array-style',
            'src/ArrayStyle.php',
        ]);
        self::assertStringContainsString('array-style', $output);

        $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            '--config',
            'mago-overrides.toml',
            'format',
            'src/Input.php',
        ]);
        self::assertFileEquals(
            __DIR__ . '/Fixtures/Formatter/ExpectedDoubleQuotes.php',
            $this->workspace . '/src/Input.php',
        );

        $output = $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            '--config',
            'mago-disabled.toml',
            'lint',
            'src/ArrayStyle.php',
        ]);
        self::assertStringNotContainsString('array-style', $output);

        $output = $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            '--config',
            'mago-disabled.toml',
            'extension',
            'list',
            '--json',
        ]);
        self::assertStringContainsString('"hosts": []', $output);
    }

    public function testComposerArchiveContainsOnlyDistributedFiles(): void
    {
        $this->executeCommand([
            'composer',
            '--working-dir',
            dirname(__DIR__, levels: 2),
            'archive',
            '--format=tar',
            '--dir',
            $this->workspace,
            '--file=package',
            '--no-interaction',
            '--no-plugins',
            '--no-scripts',
        ]);

        $archive = 'phar://' . $this->workspace . '/package.tar/';
        self::assertFileExists($archive . 'yii2.mago.toml');
        self::assertFileExists($archive . 'bin/mago-yii2-worker.php');
        self::assertFileExists($archive . 'src/Yii2Extension.php');
        self::assertFileExists($archive . 'composer.json');
        self::assertFileDoesNotExist($archive . 'mago.toml');
        self::assertFileDoesNotExist($archive . 'tests/Integration/ConsumerTest.php');
        self::assertFileDoesNotExist($archive . 'tests/consumer/composer.json');
        self::assertFileDoesNotExist($archive . 'vendor/autoload.php');
        self::assertFileDoesNotExist($archive . 'composer.lock');
    }

    private function copyConsumerFile(string $path): void
    {
        self::assertTrue(copy(dirname(__DIR__) . '/consumer/' . $path, $this->workspace . '/' . $path));
    }

    private function copyIntegrationFixture(string $fixture, string $destination): void
    {
        self::assertTrue(copy(__DIR__ . '/Fixtures/' . $fixture, $this->workspace . '/' . $destination));
    }

    private function installPackage(bool $symlink = true): void
    {
        $this->copyConsumerFile('composer.json');
        $this->copyConsumerFile('mago.toml');
        $this->copyConsumerFile('mago-overrides.toml');
        $this->copyConsumerFile('mago-disabled.toml');
        $this->copyConsumerFile('src/Example.php');

        $root = dirname(__DIR__, levels: 2);
        $package = 'ilyakabanov/mago-yii2-extension';
        $repositories = [
            [
                'type' => 'path',
                'url' => $root,
                'options' => ['symlink' => $symlink, 'versions' => [$package => 'dev-main']],
            ],
        ];

        $lock = file_get_contents($root . '/composer.lock');
        self::assertIsString($lock);
        /** @var array{packages: list<array{name: string, version: string}>} $locked */
        $locked = json_decode($lock, associative: true, flags: JSON_THROW_ON_ERROR);

        // Reuse the installed SDK and its dependencies: no downloads or version drift.
        foreach ($locked['packages'] as $dependency) {
            $repositories[] = [
                'type' => 'path',
                'url' => $root . '/vendor/' . $dependency['name'],
                'options' => [
                    'symlink' => true,
                    'versions' => [$dependency['name'] => $dependency['version']],
                ],
            ];
        }

        $repositories[] = ['packagist.org' => false];
        $manifest = file_get_contents($this->workspace . '/composer.json');
        self::assertIsString($manifest);
        /** @var array<string, mixed> $consumer */
        $consumer = json_decode($manifest, associative: true, flags: JSON_THROW_ON_ERROR);
        $consumer['repositories'] = $repositories;
        file_put_contents(
            $this->workspace . '/composer.json',
            json_encode($consumer, flags: JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );

        $this->executeCommand([
            'composer',
            'install',
            '--no-interaction',
            '--no-progress',
            '--no-plugins',
            '--no-scripts',
        ]);
    }

    /** @param non-empty-list<string> $command */
    private function executeCommand(array $command, int $expectedExit = 0): string
    {
        $stream = tmpfile();
        self::assertIsResource($stream);
        $pipes = [];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => $stream, 2 => $stream], $pipes, $this->workspace);
        self::assertIsResource($process);
        self::assertIsResource($pipes[0] ?? null);
        fclose($pipes[0]);
        $exit = proc_close($process);
        rewind($stream);
        $output = stream_get_contents($stream);
        fclose($stream);
        self::assertIsString($output);
        self::assertSame($expectedExit, $exit, $output);

        return $output;
    }
}
