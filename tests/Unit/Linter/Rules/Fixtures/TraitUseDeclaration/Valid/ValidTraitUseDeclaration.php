<?php

declare(strict_types=1);

namespace Fixture;

final class ValidTraitUseDeclaration
{
    use FirstTrait;
    use SecondTrait {
        SecondTrait::run as private runSecondTrait;
    }

    public function run(): void
    {
    }
}
