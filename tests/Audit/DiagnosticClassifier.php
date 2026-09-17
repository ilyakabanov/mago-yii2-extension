<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

final class DiagnosticClassifier
{
    /**
     * @param list<array{file: string, source: string, severity: int, type: string, line: int, column: int, fixable: bool}> $phpcs
     * @param list<array{file: string, code: string, severity: int, type: string, line: int, column: int}> $mago
     */
    public function countExactLintMatches(array $phpcs, array $mago): int
    {
        $available = [];
        foreach ($mago as $diagnostic) {
            $key = $this->locationKey(
                $diagnostic['file'],
                $diagnostic['line'],
                $diagnostic['column'],
                $diagnostic['type'],
                $diagnostic['severity'],
            );
            $available[$key] = ($available[$key] ?? 0) + 1;
        }

        $matches = 0;
        foreach ($phpcs as $diagnostic) {
            $key = $this->locationKey(
                $diagnostic['file'],
                $diagnostic['line'],
                $diagnostic['column'],
                $diagnostic['type'],
                $diagnostic['severity'],
            );
            if (($available[$key] ?? 0) === 0) {
                continue;
            }
            $available[$key]--;
            $matches++;
        }

        return $matches;
    }

    /**
     * @param array{
     *     before: int,
     *     lint-exact: int,
     *     lint-reported: int,
     *     after-format: int,
     *     formatter-succeeded: bool,
     *     formatter-idempotent: bool
     * } $observation
     */
    public function classify(array $observation): string
    {
        if ($observation['lint-exact'] === $observation['before']) {
            return 'lint';
        }
        if (
            $observation['formatter-succeeded']
            && $observation['formatter-idempotent']
            && $observation['after-format'] === 0
        ) {
            return 'formatter';
        }
        if (
            $observation['lint-exact'] > 0
            || $observation['lint-reported'] > 0
            || $observation['after-format'] < $observation['before']
            || $observation['formatter-succeeded'] && !$observation['formatter-idempotent']
        ) {
            return 'different';
        }

        return 'unsupported';
    }

    private function locationKey(string $file, int $line, int $column, string $type, int $severity): string
    {
        return sprintf('%s:%d:%d:%s:%d', $file, $line, $column, $type, $severity);
    }
}
