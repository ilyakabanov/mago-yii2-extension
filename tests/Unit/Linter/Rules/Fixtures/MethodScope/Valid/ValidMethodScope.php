<?php

declare(strict_types=1);

namespace Fixture;

final class ValidMethodScope
{
    public function __construct(public readonly string $value)
    {
    }

    protected static function build(): self
    {
        return new self('value');
    }

    private function reset(): void
    {
    }
}
