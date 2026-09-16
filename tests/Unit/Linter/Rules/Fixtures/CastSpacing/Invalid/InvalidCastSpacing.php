<?php

declare(strict_types=1);

namespace Fixture;

final class InvalidCastSpacing
{
    public function cast(mixed $value): void
    {
        $both = ( INT ) $value;
        $afterOpen = ( string) $value;
        $beforeClose = (float ) $value;
        $tabs = (	bool	) $value;
        $binary = (   binary   ) $value;
    }
}
