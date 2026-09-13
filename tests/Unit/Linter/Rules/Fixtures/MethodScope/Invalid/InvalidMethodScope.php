<?php

declare(strict_types=1);

namespace Fixture;

final class InvalidMethodScope
{
    function missingVisibility(): void
    {
    }

    static function missingStaticVisibility(): void
    {
    }

    function __construct(public readonly string $value)
    {
    }
}
