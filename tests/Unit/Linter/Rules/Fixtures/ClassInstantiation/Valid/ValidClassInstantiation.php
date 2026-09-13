<?php

declare(strict_types=1);

namespace Fixture;

final class ValidClassInstantiation
{
    public function create(string $className): void
    {
        $named = new Service();
        $dynamic = new $className();
        $anonymous = new class {
        };
    }
}
