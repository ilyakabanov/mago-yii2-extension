<?php

declare(strict_types=1);

namespace Fixture;

final class InvalidShortFormTypeKeywords
{
    public function cast(mixed $value): void
    {
        $bool = (boolean) $value;
        $spaced = ( BOOLEAN ) $value;
        $integer = (integer) $value;
    }
}
