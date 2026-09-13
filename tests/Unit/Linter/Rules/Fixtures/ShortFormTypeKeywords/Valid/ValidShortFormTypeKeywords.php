<?php

declare(strict_types=1);

namespace Fixture;

final class ValidShortFormTypeKeywords
{
    public function cast(mixed $value): void
    {
        $bool = (bool) $value;
        $int = (int) $value;
        $double = (double) $value; // The PHPCS sniff only covers boolean and integer casts.
    }
}
