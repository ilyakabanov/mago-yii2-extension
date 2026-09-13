<?php

declare(strict_types=1);

namespace Fixture;

final class InvalidPropertyDeclaration
{
    var $legacy;
    public string $first, $second;
    string $missingVisibility;
    static public string $staticBeforeVisibility;
    readonly public string $readonlyBeforeVisibility;
}
