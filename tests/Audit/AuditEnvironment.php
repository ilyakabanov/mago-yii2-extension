<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

use RuntimeException;

final class AuditEnvironment
{
    private const PACKAGE = 'ilyakabanov/mago-yii2-extension';

    /** @param array{phpcs: string, yii2-coding-standards: string, mago: string} $versions */
    public function __construct(
        private readonly AuditPaths $paths,
        private readonly array $versions,
        private readonly ProcessRunner $processes,
        private readonly ComposerInstaller $composer,
    ) {}

    public function prepare(): void
    {
        $this->preparePhpcs();
        $this->prepareMago();
        $this->prepareMagoConfiguration();
        $this->verifyVersions();
    }

    private function preparePhpcs(): void
    {
        $configuration = [
            'require' => [
                'squizlabs/php_codesniffer' => $this->versions['phpcs'],
                'yiisoft/yii2-coding-standards' => $this->versions['yii2-coding-standards'],
            ],
            'config' => [
                'allow-plugins' => false,
                'preferred-install' => 'source',
                'sort-packages' => true,
            ],
        ];
        $requiredFile = $this->paths->phpcsStandardsSource . '/PSR12/Tests/Files/FileHeaderUnitTest.inc';
        $this->composer->ensure(
            directory: $this->paths->phpcsDirectory,
            configuration: $configuration,
            installPreference: '--prefer-source',
            requiredFile: $requiredFile,
        );
    }

    private function prepareMago(): void
    {
        $configuration = [
            'repositories' => [[
                'type' => 'path',
                'url' => $this->paths->projectRoot,
                'options' => [
                    'symlink' => true,
                    'versions' => [self::PACKAGE => 'dev-audit'],
                ],
            ]],
            'require' => [
                self::PACKAGE => 'dev-audit',
                'carthage-software/mago' => $this->versions['mago'],
            ],
            'config' => [
                'allow-plugins' => false,
                'sort-packages' => true,
            ],
        ];
        $this->composer->ensure(
            directory: $this->paths->magoDirectory,
            configuration: $configuration,
            installPreference: '--prefer-dist',
            requiredFile: $this->paths->magoBinary,
        );
    }

    private function prepareMagoConfiguration(): void
    {
        $configuration = <<<'TOML'
            #:schema vendor/carthage-software/mago/schema.json
            version = "1"
            php-version = "8.1"
            extends = "vendor/ilyakabanov/mago-yii2-extension/yii2.mago.toml"

            [source]
            paths = ["cases"]
            TOML;

        if (file_put_contents($this->paths->magoConfiguration, $configuration . "\n") === false) {
            throw new RuntimeException('Unable to write Mago audit configuration.');
        }
    }

    private function verifyVersions(): void
    {
        $phpcs = $this->processes->run([$this->paths->phpcsBinary, '--version'])->stdout;
        if (!str_contains($phpcs, 'version ' . $this->versions['phpcs'] . ' ')) {
            throw new RuntimeException('Unexpected PHPCS version: ' . trim($phpcs));
        }

        $mago = $this->processes->run([$this->paths->magoBinary, '--version'])->stdout;
        if (trim($mago) !== 'mago ' . $this->versions['mago']) {
            throw new RuntimeException('Unexpected Mago version: ' . trim($mago));
        }

        $installedPath = $this->paths->phpcsDirectory . '/vendor/composer/installed.php';
        /** @var array{versions: array<string, array{pretty_version?: string}>} $installed */
        $installed = require $installedPath;
        $yiiVersion = $installed['versions']['yiisoft/yii2-coding-standards']['pretty_version'] ?? null;
        if ($yiiVersion !== $this->versions['yii2-coding-standards']) {
            throw new RuntimeException('Unexpected Yii2 Coding Standards version: ' . (string) $yiiVersion);
        }
    }
}
