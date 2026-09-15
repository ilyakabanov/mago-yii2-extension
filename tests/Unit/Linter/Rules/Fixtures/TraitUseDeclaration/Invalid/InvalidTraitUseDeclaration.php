<?php

declare(strict_types=1);

namespace Fixture;

final class InvalidTraitUseDeclaration
{
    public string $value;

    use FirstTrait;

    public function run(): void
    {
    }

    use ThirdTrait;
}
