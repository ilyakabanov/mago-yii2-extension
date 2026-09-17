<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

final class ProcessResult
{
    /** @param list<string> $command */
    public function __construct(
        public readonly array $command,
        public readonly int $exitCode,
        public readonly string $stdout,
        public readonly string $stderr,
    ) {}
}
