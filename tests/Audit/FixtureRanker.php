<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

final class FixtureRanker
{
    /** @param array<string, list<string>> $preferredFixtures */
    public function __construct(
        private readonly string $projectRoot,
        private readonly array $preferredFixtures,
    ) {}

    public function rank(string $file, string $sniff): int
    {
        [$standard, $category, $name] = explode('.', $sniff);
        $target = '/Standards/' . $standard . '/Tests/' . $category . '/' . $name . 'UnitTest';
        $normalized = str_replace(search: '\\', replace: '/', subject: $file);
        $projectFile = $this->projectRelativePath($file);
        if ($projectFile !== null && in_array($projectFile, $this->preferredFixtures[$sniff] ?? [], strict: true)) {
            return 0;
        }
        if (str_contains($normalized, $target)) {
            return 1;
        }

        return $projectFile === null ? 3 : 2;
    }

    private function projectRelativePath(string $file): ?string
    {
        $file = $this->canonicalPath($file);
        $projectRoot = $this->canonicalPath($this->projectRoot);
        if (!str_starts_with($file, $projectRoot . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return substr($file, offset: strlen($projectRoot) + 1);
    }

    private function canonicalPath(string $path): string
    {
        $canonical = realpath($path);

        return $canonical === false ? $path : $canonical;
    }
}
