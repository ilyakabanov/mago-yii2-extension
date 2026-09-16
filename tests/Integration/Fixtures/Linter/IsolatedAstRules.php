<?php

/** File header. */
declare(strict_types=1);

namespace Fixture;

use \Vendor\Package\Service;

final class IsolatedAstRules
{
    const STATUS = 'ready';

    public function check(mixed $value): void
    {
        if ($value) {
        } else if ($value === null) {
        }

        $bool = (boolean) $value;
        $spaced = ( int ) $value;
        $service = new Service();
    }

    function missingVisibility(): void
    {
    }
} // class comment
