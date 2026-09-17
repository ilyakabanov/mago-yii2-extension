<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

use RuntimeException;

final class ComposerInstaller
{
    public function __construct(
        private readonly ProcessRunner $processes,
    ) {}

    /** @param array<string, mixed> $configuration */
    public function ensure(
        string $directory,
        array $configuration,
        string $installPreference,
        string $requiredFile,
    ): void {
        $changed = $this->writeConfiguration($directory, $configuration);
        if (!$changed && is_file($requiredFile)) {
            return;
        }

        $this->processes->run([
            'composer',
            'update',
            '--working-dir=' . $directory,
            '--no-interaction',
            '--no-progress',
            $installPreference,
        ]);
    }

    /** @param array<string, mixed> $configuration */
    private function writeConfiguration(string $directory, array $configuration): bool
    {
        $this->ensureDirectory($directory);
        $json = json_encode($configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        $path = $directory . '/composer.json';
        $current = is_file($path) ? file_get_contents($path) : false;
        if ($current === $json) {
            return false;
        }

        if (file_put_contents($path, $json) === false) {
            throw new RuntimeException('Unable to write ' . $path);
        }

        return true;
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }
        if (!mkdir($directory, recursive: true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create directory: ' . $directory);
        }
    }
}
