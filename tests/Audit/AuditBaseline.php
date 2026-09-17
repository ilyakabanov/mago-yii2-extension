<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

use RuntimeException;

final class AuditBaseline
{
    public function __construct(
        private readonly string $baselinePath,
        private readonly string $actualPath,
    ) {}

    /** @param array<string, mixed> $report */
    public function update(array $report): void
    {
        $this->write($this->baselinePath, $this->encode($report));
        echo 'Updated ', $this->baselinePath, "\n";
    }

    /** @param array<string, mixed> $report */
    public function assertMatches(array $report): void
    {
        if (!is_file($this->baselinePath)) {
            throw new RuntimeException('Audit baseline is missing. Generate it with php tests/Audit/run.php --update.');
        }

        $expected = file_get_contents($this->baselinePath);
        if ($expected === false) {
            throw new RuntimeException('Unable to read audit baseline.');
        }

        $actual = $this->encode($report);
        if (hash_equals($expected, $actual)) {
            return;
        }

        $this->write($this->actualPath, $actual);
        throw new RuntimeException('Differential audit differs from the recorded baseline. Actual report: '
        . $this->actualPath);
    }

    /** @param array<string, mixed> $report */
    private function encode(array $report): string
    {
        return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    }

    private function write(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to write ' . $path);
        }
    }
}
