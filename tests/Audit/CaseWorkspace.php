<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class CaseWorkspace
{
    public function __construct(
        private readonly string $casesDirectory,
    ) {}

    /**
     * @param list<string> $sourceFiles
     * @return array<string, string>
     */
    public function copyForSniff(string $sniff, array $sourceFiles): array
    {
        $directory = $this->casesDirectory . '/' . str_replace(search: '.', replace: '-', subject: $sniff);
        if (!is_dir($directory) && !mkdir($directory, recursive: true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create audit case directory: ' . $directory);
        }

        $copies = [];
        foreach (array_values(array_unique($sourceFiles)) as $source) {
            $name = substr(sha1($source), offset: 0, length: 12) . '-' . basename($source) . '.php';
            $destination = $directory . '/' . $name;
            if (!copy($source, $destination)) {
                throw new RuntimeException('Unable to copy audit fixture: ' . $source);
            }
            $copies[$source] = $this->canonicalPath($destination);
        }

        return $copies;
    }

    public function reset(): void
    {
        if (!is_dir($this->casesDirectory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->casesDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $path = $file->getPathname();
            if ($file->isDir()) {
                rmdir($path);
                continue;
            }
            unlink($path);
        }
        rmdir($this->casesDirectory);
    }

    private function canonicalPath(string $path): string
    {
        $canonical = realpath($path);

        return $canonical === false ? $path : $canonical;
    }
}
