<?php

declare(strict_types=1);

namespace Fixture;

final class ValidElseIfDeclaration
{
    public function choose(bool $first, bool $second): void
    {
        if ($first) {
        } elseif ($second) {
        }

        if ($first) {
        } else {
            if ($second) {
            }
        }
    }
}
