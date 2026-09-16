<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Integration;

use PHPUnit\Framework\Attributes\TestWith;
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

    #[TestWith([true])]
    #[TestWith([false])]
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

        $this->copyIntegrationFixture('Linter/IsolatedAstRules.php', 'src/IsolatedAstRules.php');
        $output = $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'lint',
            'src/IsolatedAstRules.php',
        ], expectedExit: 1);
        self::assertStringContainsString('yii2/else-if-declaration', $output);
        self::assertStringContainsString('yii2/short-form-type-keywords', $output);
        self::assertStringContainsString('yii2/cast-spacing', $output);
        self::assertStringContainsString('yii2/closing-brace', $output);
        self::assertStringContainsString('yii2/method-scope', $output);
        self::assertStringContainsString('yii2/constant-visibility', $output);
        self::assertStringContainsString('yii2/import-statement', $output);

        $this->copyIntegrationFixture('Linter/StructuralDeclarationRules.php', 'src/StructuralDeclarationRules.php');
        $output = $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'lint',
            'src/StructuralDeclarationRules.php',
        ], expectedExit: 1);
        self::assertStringContainsString('yii2/property-declaration', $output);
        self::assertStringContainsString('yii2/method-declaration', $output);
        self::assertStringContainsString('yii2/trait-use-declaration', $output);

        $this->copyIntegrationFixture('Linter/ByteOrderMark.php', 'src/ByteOrderMark.php');
        $output = $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'lint',
            '--only',
            'yii2/byte-order-mark',
            'src/ByteOrderMark.php',
        ], expectedExit: 1);
        self::assertStringContainsString('yii2/byte-order-mark', $output);

        $this->copyIntegrationFixture('Linter/AlternativePhpTags.php', 'src/AlternativePhpTags.php');
        $output = $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'lint',
            '--only',
            'yii2/disallow-alternative-php-tags',
            'src/AlternativePhpTags.php',
        ], expectedExit: 1);
        self::assertStringContainsString('yii2/disallow-alternative-php-tags', $output);

        mkdir($this->workspace . '/src/tests');
        $excludedMethodPath = 'src/tests/AllowedMethodNameTest.php';
        $this->copyIntegrationFixture('Linter/AllowedMethodName.php', $excludedMethodPath);
        $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'lint',
            '--only',
            'yii2/method-declaration',
            $excludedMethodPath,
        ]);

        $excludedMethodPath = 'src/tests/AllowedMethodNameCest.php';
        $this->copyIntegrationFixture('Linter/AllowedMethodName.php', $excludedMethodPath);
        $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'lint',
            '--only',
            'yii2/method-declaration',
            $excludedMethodPath,
        ]);
    }

    public function testPresetEnablesReadyMagoRules(): void
    {
        $this->installPackage();

        $fixtures = [
            'array-style' => 'ArrayStyle.php',
            'block-statement' => 'BlockStatement.php',
            'class-name' => 'ClassName.php',
            'constant-name' => 'ConstantName.php',
            'lowercase-keyword' => 'LowercaseKeyword.php',
            'lowercase-type-hint' => 'LowercaseTypeHint.php',
            'method-name' => 'MethodName.php',
            'no-closing-tag' => 'NoClosingTag.php',
            'no-short-opening-tag' => 'NoShortOpeningTag.php',
            'no-side-effects-with-declarations' => 'NoSideEffectsWithDeclarations.php',
            'optional-param-order' => 'OptionalParamOrder.php',
            'require-namespace' => 'RequireNamespace.php',
            'single-class-per-file' => 'SingleClassPerFile.php',
        ];
        $rules = [...array_keys($fixtures), 'no-trailing-space'];

        $output = $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'config',
            '--show',
            'linter',
        ]);
        /** @var array{rules: array<string, array{enabled: bool, level: string}>} $config */
        $config = json_decode($output, associative: true, flags: JSON_THROW_ON_ERROR);
        foreach ($rules as $rule) {
            self::assertTrue($config['rules'][$rule]['enabled'] ?? false, $rule);
            self::assertSame('Error', $config['rules'][$rule]['level'] ?? null, $rule);
        }

        $invalidPaths = [];
        foreach ($fixtures as $fixture) {
            $path = 'src/' . $fixture;
            $this->copyIntegrationFixture('Linter/' . $fixture, $path);
            $invalidPaths[] = $path;
        }
        $trailingSpacePath = 'src/NoTrailingSpace.php';
        file_put_contents(
            filename: $this->workspace . '/' . $trailingSpacePath,
            data: "<?php\n\n// Trailing space.\x20\n",
        );
        $invalidPaths[] = $trailingSpacePath;

        $output = $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'lint',
            ...$invalidPaths,
        ], expectedExit: 1);
        foreach ($rules as $rule) {
            self::assertStringContainsString($rule, $output);
        }

        mkdir($this->workspace . '/web');
        $this->copyIntegrationFixture('Linter/ExcludedEntrypoint.php', 'web/index.php');
        $this->executeCommand([PHP_BINARY, 'vendor/bin/mago', 'lint', 'web/index.php']);

        $migration = 'src/m250101_000000_create_table.php';
        $this->copyIntegrationFixture('Linter/ExcludedMigration.php', $migration);
        $output = $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'lint',
            $migration,
        ], expectedExit: 1);
        self::assertStringContainsString('class-name', $output);
        self::assertStringNotContainsString('require-namespace', $output);
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

        $formatterContractPath = 'src/FormatterCoveredRules.php';
        $this->copyIntegrationFixture('Formatter/FormatterCoveredRulesInput.php', $formatterContractPath);
        $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'format',
            '--check',
            $formatterContractPath,
        ], expectedExit: 1);
        $this->executeCommand([PHP_BINARY, 'vendor/bin/mago', 'format', $formatterContractPath]);
        self::assertFileEquals(
            __DIR__ . '/Fixtures/Formatter/FormatterCoveredRulesExpected.php',
            $this->workspace . '/' . $formatterContractPath,
        );
        $this->executeCommand([PHP_BINARY, 'vendor/bin/mago', 'format', '--check', $formatterContractPath]);

        $functionDeclarationContractPath = 'src/FunctionDeclarationLayout.php';
        $this->copyIntegrationFixture('Formatter/FunctionDeclarationLayoutInput.php', $functionDeclarationContractPath);
        $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'format',
            '--check',
            $functionDeclarationContractPath,
        ], expectedExit: 1);
        $this->executeCommand([PHP_BINARY, 'vendor/bin/mago', 'format', $functionDeclarationContractPath]);
        self::assertFileEquals(
            __DIR__ . '/Fixtures/Formatter/FunctionDeclarationLayoutExpected.php',
            $this->workspace . '/' . $functionDeclarationContractPath,
        );
        $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'format',
            '--check',
            $functionDeclarationContractPath,
        ]);

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

    public function testProjectNormalizesWhitespaceAndFileLayout(): void
    {
        $this->installPackage();

        $whitespaceFileLayoutPath = 'src/WhitespaceFileLayout.php';
        /** @var string $whitespaceFileLayoutInput */
        $whitespaceFileLayoutInput = require __DIR__ . '/Fixtures/Formatter/WhitespaceFileLayoutInput.php';
        /** @var string $whitespaceFileLayoutExpected */
        $whitespaceFileLayoutExpected = require __DIR__ . '/Fixtures/Formatter/WhitespaceFileLayoutExpected.php';

        self::assertIsString($whitespaceFileLayoutInput);
        self::assertIsString($whitespaceFileLayoutExpected);
        self::assertStringContainsString("\r\n", $whitespaceFileLayoutInput);
        self::assertStringContainsString("\r", str_replace(
            search: "\r\n",
            replace: '',
            subject: $whitespaceFileLayoutInput,
        ));
        self::assertStringContainsString("\t", $whitespaceFileLayoutInput);
        self::assertMatchesRegularExpression('/[ \t]+(?:\r\n|\r|$)/', $whitespaceFileLayoutInput);
        self::assertFalse(str_ends_with($whitespaceFileLayoutInput, "\n"));
        self::assertFalse(str_ends_with($whitespaceFileLayoutInput, "\r"));
        self::assertStringNotContainsString("\r", $whitespaceFileLayoutExpected);
        self::assertStringNotContainsString("\t", $whitespaceFileLayoutExpected);
        self::assertStringEndsWith("}\n", $whitespaceFileLayoutExpected);

        self::assertIsInt(file_put_contents(
            $this->workspace . '/' . $whitespaceFileLayoutPath,
            $whitespaceFileLayoutInput,
        ));
        $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'format',
            '--check',
            $whitespaceFileLayoutPath,
        ], expectedExit: 1);
        $this->executeCommand([PHP_BINARY, 'vendor/bin/mago', 'format', $whitespaceFileLayoutPath]);
        $whitespaceFileLayoutOutput = file_get_contents($this->workspace . '/' . $whitespaceFileLayoutPath);
        self::assertIsString($whitespaceFileLayoutOutput);
        self::assertSame($whitespaceFileLayoutExpected, $whitespaceFileLayoutOutput);
        $this->executeCommand([
            PHP_BINARY,
            'vendor/bin/mago',
            'format',
            '--check',
            $whitespaceFileLayoutPath,
        ]);
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

    private function copyIntegrationFixture(string $fixture, string $destination): void
    {
        self::assertTrue(copy(__DIR__ . '/Fixtures/' . $fixture, $this->workspace . '/' . $destination));
    }

    private function installPackage(bool $symlink = true): void
    {
        $consumerDirectory = dirname(__DIR__) . '/consumer/';
        foreach ([
            'composer.json',
            'mago.toml',
            'mago-overrides.toml',
            'mago-disabled.toml',
            'src/Example.php',
        ] as $path) {
            self::assertTrue(copy($consumerDirectory . $path, $this->workspace . '/' . $path));
        }

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
