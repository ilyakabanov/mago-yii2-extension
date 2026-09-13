<?php

declare(strict_types=1);

namespace Fixture;

final class InvalidTraitUseDeclaration
{
    public string $value;

    use FirstTrait, SecondTrait;

    public function run(): void
    {
    }

    use ThirdTrait;
}
