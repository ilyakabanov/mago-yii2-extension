<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

final class FixtureCorpus
{
    private readonly FixtureFinder $finder;
    private readonly FixtureSelector $selector;
    private readonly CaseWorkspace $workspace;

    /** @param array<string, list<string>> $preferredFixtures */
    public function __construct(
        string $projectRoot,
        string $standardsRoot,
        string $casesDirectory,
        array $preferredFixtures,
    ) {
        $this->finder = new FixtureFinder($projectRoot, $standardsRoot);
        $this->selector = new FixtureSelector($projectRoot, $standardsRoot, $preferredFixtures);
        $this->workspace = new CaseWorkspace($casesDirectory);
    }

    /** @return list<string> */
    public function sourceFiles(): array
    {
        return $this->finder->sourceFiles();
    }

    /**
     * @param list<array{file: string, source: string, severity: int, type: string, line: int, column: int, fixable: bool}> $diagnostics
     * @param list<string> $sniffs
     * @return array<string, string>
     */
    public function selectRepresentativeFixtures(array $diagnostics, array $sniffs): array
    {
        return $this->selector->selectRepresentativeFixtures($diagnostics, $sniffs);
    }

    /**
     * @param list<string> $sourceFiles
     * @return array<string, string>
     */
    public function copyForSniff(string $sniff, array $sourceFiles): array
    {
        return $this->workspace->copyForSniff($sniff, $sourceFiles);
    }

    public function resetCases(): void
    {
        $this->workspace->reset();
    }

    public function label(string $file): string
    {
        return $this->selector->label($file);
    }
}
