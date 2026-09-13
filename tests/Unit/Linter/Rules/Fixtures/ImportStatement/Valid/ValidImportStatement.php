<?php

declare(strict_types=1);

namespace Fixture;

use Vendor\Package\Service;
use Vendor\Package\{FirstService, SecondService};
use function Vendor\Package\helper;
use const Vendor\Package\VERSION;

final class ValidImportStatement
{
    use ServiceTrait;

    public function callback(string $value): callable
    {
        return function () use ($value): string {
            return $value;
        };
    }
}
