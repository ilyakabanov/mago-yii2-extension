<?php

namespace Fixture;

use Vendor\Package\Http\Client\Factory;

final class FormatterCoveredRules implements FirstContract, SecondContract
{
    use FirstTrait;
    use SecondTrait;

    public static string $value;

    final public static function create()
    {
        $factory = Factory::class;
        new Factory();

        return new $factory();
    }

    public function buildLabel(int $left, int $right, bool $enabled): string
    {
        ++$left;
        $left--;
        --$right;
        $right++;
        $total = $left + $right;
        $matches = $enabled && $left === $right;
        $selected = $matches ? $total : $right;

        return 'value:' . $selected;
    }
}
