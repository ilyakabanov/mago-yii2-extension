<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

use RuntimeException;

final class FixtureSelector
{
    private readonly FixtureRanker $ranker;

    /** @param array<string, list<string>> $preferredFixtures */
    public function __construct(
        private readonly string $projectRoot,
        private readonly string $standardsRoot,
        array $preferredFixtures,
    ) {
        $this->ranker = new FixtureRanker($projectRoot, $preferredFixtures);
    }

    /**
     * @param list<array{file: string, source: string, severity: int, type: string, line: int, column: int, fixable: bool}> $diagnostics
     * @param list<string> $sniffs
     * @return array<string, string>
     */
    public function selectRepresentativeFixtures(array $diagnostics, array $sniffs): array
    {
        $candidates = [];
        foreach ($diagnostics as $diagnostic) {
            $sniff = $this->sniffForCode($diagnostic['source'], $sniffs);
            if ($sniff === null) {
                continue;
            }
            $candidates[$diagnostic['source']][] = $diagnostic['file'];
        }

        $selected = [];
        foreach ($candidates as $code => $files) {
            $sniff = $this->sniffForCode($code, $sniffs);
            if ($sniff === null) {
                continue;
            }
            usort($files, function (string $left, string $right) use ($sniff): int {
                $comparison = $this->ranker->rank($left, $sniff) <=> $this->ranker->rank($right, $sniff);

                return $comparison !== 0 ? $comparison : strcmp($left, $right);
            });
            $selected[$code] = $files[0];
        }
        ksort($selected);

        return $selected;
    }

    public function label(string $file): string
    {
        $file = $this->canonicalPath($file);
        $standardsRoot = $this->canonicalPath($this->standardsRoot);
        if (str_starts_with($file, $standardsRoot . DIRECTORY_SEPARATOR)) {
            return 'phpcs/' . substr($file, offset: strlen($standardsRoot) + 1);
        }

        $projectRoot = $this->canonicalPath($this->projectRoot);
        if (str_starts_with($file, $projectRoot . DIRECTORY_SEPARATOR)) {
            return 'project/' . substr($file, offset: strlen($projectRoot) + 1);
        }

        throw new RuntimeException('Fixture is outside known roots: ' . $file);
    }

    /** @param list<string> $sniffs */
    private function sniffForCode(string $code, array $sniffs): ?string
    {
        foreach ($sniffs as $sniff) {
            if (str_starts_with($code, $sniff . '.')) {
                return $sniff;
            }
        }

        return null;
    }

    private function canonicalPath(string $path): string
    {
        $canonical = realpath($path);

        return $canonical === false ? $path : $canonical;
    }
}
