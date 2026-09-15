<?php

namespace Fixture;

class StructuralDeclarationRules
{
    var $legacy;

    use FirstTrait;

    final public static function _legacy(): void
    {
    }

    use SecondTrait;
}
