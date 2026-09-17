<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class FixtureFinder
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly string $standardsRoot,
    ) {}

    /** @return list<string> */
    public function sourceFiles(): array
    {
        $files = $this->phpcsFixtures();
        foreach ([
            $this->projectRoot . '/tests/Unit/Linter/Rules/Fixtures',
            $this->projectRoot . '/tests/Integration/Fixtures',
        ] as $fixtureRoot) {
            array_push($files, ...$this->phpFixtures($fixtureRoot));
        }
        sort($files);

        return array_values(array_unique($files));
    }

    /** @return list<string> */
    private function phpcsFixtures(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->standardsRoot, FilesystemIterator::SKIP_DOTS),
        );
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || preg_match('/UnitTest(?:\.\d+)?\.inc$/', $file->getFilename()) !== 1) {
                continue;
            }
            $files[] = $this->realPath($file);
        }

        return $files;
    }

    /** @return list<string> */
    private function phpFixtures(string $root): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $files[] = $this->realPath($file);
        }

        return $files;
    }

    private function realPath(SplFileInfo $file): string
    {
        $path = $file->getRealPath();
        if ($path === false) {
            throw new RuntimeException('Unable to resolve fixture path: ' . $file->getPathname());
        }

        return $path;
    }
}
