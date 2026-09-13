<?php

namespace Fixture;

use Vendor\Package\{Http\Client\Factory};

class StructuralDeclarationRules
{
    var $legacy;

    use FirstTrait, SecondTrait;

    public final static function _legacy(): void
    {
    }
}
