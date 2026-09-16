<?php

declare(strict_types=1);

namespace Fixture;

final class ValidCastSpacing
{
    public function cast(mixed $value): void
    {
        $array = (array) $value;
        $bool = (bool) $value;
        $float = (float) $value;
        $int = (int) $value;
        $object = (object) $value;
        $string = (string) $value;
        $binary = (binary) $value;
        $boolean = (boolean) $value;
        $double = (double) $value;
        $integer = (integer) $value;
        $negated = !$value;
        $negative = -$value;
    }
}
