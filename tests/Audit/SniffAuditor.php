<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

use RuntimeException;

final class SniffAuditor
{
    /** @param array<string, list<string>> $diagnosticRules */
    public function __construct(
        private readonly PhpcsOracle $phpcs,
        private readonly MagoClient $mago,
        private readonly FixtureCorpus $fixtures,
        private readonly DiagnosticClassifier $classifier,
        private readonly array $diagnosticRules,
    ) {}

    /**
     * @param list<string> $magoRules
     * @param array<string, string> $selected
     * @return array<string, array<string, mixed>>
     */
    public function audit(string $sniff, array $magoRules, array $selected): array
    {
        fwrite(stream: STDERR, data: 'Auditing ' . $sniff . "...\n");
        $sources = $this->sourcesForSniff($sniff, $selected);
        $copies = $this->fixtures->copyForSniff($sniff, array_values($sources));
        $files = array_values($copies);
        $before = $this->phpcs->inspect($files, $sniff);
        $lint = $this->mago->lint($magoRules, $files);
        $formatterRun = $this->mago->format($files);
        $after = $this->phpcs->inspect($files, $sniff);
        $firstHashes = $this->hashFiles($files);
        $secondFormatterRun = $this->mago->format($files);
        $secondHashes = $this->hashFiles($files);
        $idempotence = [];
        foreach ($files as $file) {
            $idempotence[$file] =
                $formatterRun->succeededFor($file)
                && $secondFormatterRun->succeededFor($file)
                && $firstHashes[$file] === $secondHashes[$file];
        }
        $execution = [
            'copies' => $copies,
            'before' => $before,
            'lint' => $lint,
            'after' => $after,
            'formatter-run' => $formatterRun,
            'formatter-idempotence' => $idempotence,
            'mago-rules' => $magoRules,
        ];

        $result = [];
        foreach ($sources as $code => $source) {
            $result[$code] = $this->buildDiagnosticResult($sniff, $code, $source, $execution);
        }

        return $result;
    }

    /**
     * @param array<string, string> $selected
     * @return array<string, string>
     */
    private function sourcesForSniff(string $sniff, array $selected): array
    {
        $sources = [];
        foreach ($selected as $code => $source) {
            if (!str_starts_with($code, $sniff . '.')) {
                continue;
            }
            $sources[$code] = $source;
        }

        return $sources;
    }

    /**
     * @param array{
     *     copies: array<string, string>,
     *     before: list<array{file: string, source: string, severity: int, type: string, line: int, column: int, fixable: bool}>,
     *     lint: list<array{file: string, code: string, severity: int, type: string, line: int, column: int}>,
     *     after: list<array{file: string, source: string, severity: int, type: string, line: int, column: int, fixable: bool}>,
     *     formatter-run: FormatterRun,
     *     formatter-idempotence: array<string, bool>,
     *     mago-rules: list<string>
     * } $execution
     * @return array<string, mixed>
     */
    private function buildDiagnosticResult(string $sniff, string $code, string $source, array $execution): array
    {
        $file = $execution['copies'][$source];
        $before = $this->phpcsDiagnosticsFor($execution['before'], $code, $file);
        if ($before === []) {
            throw new RuntimeException(sprintf(
                'Representative fixture no longer emits %s after copying: %s',
                $code,
                $this->fixtures->label($source),
            ));
        }

        $magoRules = $this->diagnosticRules[$code] ?? $execution['mago-rules'];
        $lint = $this->magoDiagnosticsFor($execution['lint'], $file, $magoRules);
        $lintExact = $this->classifier->countExactLintMatches($before, $lint);
        $after = $this->phpcsDiagnosticsFor($execution['after'], $code, $file);
        $afterCount = count($after);
        $formatterRun = $execution['formatter-run'];
        $formatterIdempotent = $execution['formatter-idempotence'][$file];
        $observation = [
            'before' => count($before),
            'lint-exact' => $lintExact,
            'lint-reported' => count($lint),
            'after-format' => $afterCount,
            'formatter-succeeded' => $formatterRun->succeededFor($file),
            'formatter-idempotent' => $formatterIdempotent,
        ];

        return [
            'sniff' => $sniff,
            'fixture' => $this->fixtures->label($source),
            'classification' => $this->classifier->classify($observation),
            'mago-rules' => $magoRules,
            'occurrences' => count($before),
            'exact-lint-matches' => $lintExact,
            'mago-reports-in-fixture' => count($lint),
            'remaining-after-format' => $afterCount,
            'formatter-exit' => $formatterRun->exitCode,
            'formatter-parse-error' => $formatterRun->failedToParse($file),
            'formatter-idempotent' => $formatterIdempotent,
            'phpcs-types' => $this->uniqueValues($before, 'type'),
            'phpcs-severities' => $this->uniqueValues($before, 'severity'),
            'phpcs-fixable' => $this->uniqueValues($before, 'fixable'),
            'phpcs-locations' => $this->phpcsLocations($before),
            'mago-locations' => $this->magoLocations($lint),
            'remaining-locations' => $this->phpcsLocations($after),
        ];
    }

    /**
     * @param list<array{file: string, source: string, severity: int, type: string, line: int, column: int, fixable: bool}> $diagnostics
     * @return list<array{file: string, source: string, severity: int, type: string, line: int, column: int, fixable: bool}>
     */
    private function phpcsDiagnosticsFor(array $diagnostics, string $code, string $file): array
    {
        return array_values(array_filter(
            $diagnostics,
            static fn(array $diagnostic): bool => $diagnostic['source'] === $code && $diagnostic['file'] === $file,
        ));
    }

    /**
     * @param list<array{file: string, code: string, severity: int, type: string, line: int, column: int}> $diagnostics
     * @param list<string> $rules
     * @return list<array{file: string, code: string, severity: int, type: string, line: int, column: int}>
     */
    private function magoDiagnosticsFor(array $diagnostics, string $file, array $rules): array
    {
        return array_values(array_filter(
            $diagnostics,
            static fn(array $diagnostic): bool => $diagnostic['file'] === $file
            && in_array($diagnostic['code'], $rules, strict: true),
        ));
    }

    /**
     * @param list<string> $files
     * @return array<string, string>
     */
    private function hashFiles(array $files): array
    {
        $hashes = [];
        foreach ($files as $file) {
            $hash = hash_file('sha256', $file);
            if ($hash === false) {
                throw new RuntimeException('Unable to hash audit fixture: ' . $file);
            }
            $hashes[$file] = $hash;
        }

        return $hashes;
    }

    /**
     * @param list<array{file: string, source: string, severity: int, type: string, line: int, column: int, fixable: bool}> $diagnostics
     * @return list<string>
     */
    private function phpcsLocations(array $diagnostics): array
    {
        $locations = array_map(static fn(array $diagnostic): string => sprintf(
            '%d:%d:%s:%d:%s',
            $diagnostic['line'],
            $diagnostic['column'],
            $diagnostic['type'],
            $diagnostic['severity'],
            $diagnostic['fixable'] ? 'fixable' : 'manual',
        ), $diagnostics);
        sort($locations);

        return $locations;
    }

    /**
     * @param list<array{file: string, code: string, severity: int, type: string, line: int, column: int}> $diagnostics
     * @return list<string>
     */
    private function magoLocations(array $diagnostics): array
    {
        $locations = array_map(static fn(array $diagnostic): string => sprintf(
            '%s@%d:%d:%s:%d',
            $diagnostic['code'],
            $diagnostic['line'],
            $diagnostic['column'],
            $diagnostic['type'],
            $diagnostic['severity'],
        ), $diagnostics);
        sort($locations);

        return $locations;
    }

    /**
     * @param list<array{file: string, source: string, severity: int, type: string, line: int, column: int, fixable: bool}> $diagnostics
     * @return list<bool|int|string>
     */
    private function uniqueValues(array $diagnostics, string $key): array
    {
        $values = array_values(array_unique(array_column($diagnostics, $key), SORT_REGULAR));
        sort($values);

        return $values;
    }
}
