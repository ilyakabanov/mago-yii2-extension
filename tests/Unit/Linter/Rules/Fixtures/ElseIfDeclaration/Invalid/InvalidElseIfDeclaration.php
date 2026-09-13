<?php

declare(strict_types=1);

namespace Fixture;

final class InvalidElseIfDeclaration
{
    public function choose(bool $first, bool $second): void
    {
        if ($first) {
        } else if ($second) {
        }
    }
}
