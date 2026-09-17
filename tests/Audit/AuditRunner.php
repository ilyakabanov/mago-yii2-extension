<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

use RuntimeException;

final class AuditRunner
{
    /**
     * @param array{phpcs: string, yii2-coding-standards: string, mago: string} $versions
     * @param array<string, list<string>> $sniffs
     */
    public function __construct(
        private readonly array $versions,
        private readonly array $sniffs,
        private readonly PhpcsOracle $phpcs,
        private readonly SniffAuditor $sniffAuditor,
        private readonly FixtureCorpus $fixtures,
    ) {}

    /**
     * @return array{
     *     schema: int,
     *     oracles: array{phpcs: string, yii2-coding-standards: string, mago: string},
     *     scope: array{sniffs: int, concrete-diagnostics: int, fixture-source: string},
     *     summary: array{lint: int, formatter: int, different: int, unsupported: int, unclassified: int},
     *     diagnostics: array<string, array<string, mixed>>
     * }
     */
    public function run(): array
    {
        $this->verifySniffInventory();
        $this->fixtures->resetCases();

        fwrite(stream: STDERR, data: "Discovering concrete PHPCS diagnostics...\n");
        $discovered = $this->phpcs->inspect($this->fixtures->sourceFiles());
        $sniffNames = array_keys($this->sniffs);
        $selected = $this->fixtures->selectRepresentativeFixtures($discovered, $sniffNames);
        $this->verifyEverySniffIsExercised($selected);

        $diagnostics = [];
        foreach ($this->sniffs as $sniff => $magoRules) {
            $diagnostics += $this->sniffAuditor->audit($sniff, $magoRules, $selected);
        }
        ksort($diagnostics);

        return [
            'schema' => 1,
            'oracles' => $this->versions,
            'scope' => [
                'sniffs' => count($this->sniffs),
                'concrete-diagnostics' => count($diagnostics),
                'fixture-source' => 'PHPCS 4.0.4 upstream unit fixtures plus project contract fixtures',
            ],
            'summary' => $this->summary($diagnostics),
            'diagnostics' => $diagnostics,
        ];
    }

    private function verifySniffInventory(): void
    {
        $expected = array_keys($this->sniffs);
        $actual = $this->phpcs->listSniffs();
        sort($expected);
        if ($expected === $actual) {
            return;
        }

        throw new RuntimeException(sprintf(
            "Sniff inventory does not match Yii2 Coding Standards.\nMissing from manifest: %s\nNot active: %s",
            implode(', ', array_diff($actual, $expected)),
            implode(', ', array_diff($expected, $actual)),
        ));
    }

    /** @param array<string, string> $selected */
    private function verifyEverySniffIsExercised(array $selected): void
    {
        $missing = [];
        foreach (array_keys($this->sniffs) as $sniff) {
            foreach (array_keys($selected) as $code) {
                if (str_starts_with($code, $sniff . '.')) {
                    continue 2;
                }
            }
            $missing[] = $sniff;
        }
        if ($missing !== []) {
            throw new RuntimeException('No concrete diagnostics found for: ' . implode(', ', $missing));
        }
    }

    /**
     * @param array<string, array<string, mixed>> $diagnostics
     * @return array{lint: int, formatter: int, different: int, unsupported: int, unclassified: int}
     */
    private function summary(array $diagnostics): array
    {
        $summary = [
            'lint' => 0,
            'formatter' => 0,
            'different' => 0,
            'unsupported' => 0,
            'unclassified' => 0,
        ];
        foreach ($diagnostics as $diagnostic) {
            switch ($diagnostic['classification'] ?? null) {
                case 'lint':
                    $summary['lint']++;
                    break;
                case 'formatter':
                    $summary['formatter']++;
                    break;
                case 'different':
                    $summary['different']++;
                    break;
                case 'unsupported':
                    $summary['unsupported']++;
                    break;
                default:
                    $summary['unclassified']++;
            }
        }

        return $summary;
    }
}
