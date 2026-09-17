<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

final class FormatterRun
{
    /** @param list<string> $failedFiles */
    public function __construct(
        public readonly int $exitCode,
        private readonly array $failedFiles,
    ) {}

    public function succeededFor(string $file): bool
    {
        return $this->exitCode === 0 && !in_array($file, $this->failedFiles, strict: true);
    }

    public function failedToParse(string $file): bool
    {
        return in_array($file, $this->failedFiles, strict: true);
    }
}
