<?php

declare(strict_types=1);

namespace Fixture;

final class InvalidClassInstantiation
{
    public function create(string $className): void
    {
        $named = new Service;
        $dynamic = new $className;
    }
}
