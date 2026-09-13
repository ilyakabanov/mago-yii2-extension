<?php

declare(strict_types=1);

namespace Fixture;

final class ValidPropertyDeclaration
{
    public string $name;
    protected static array $cache = [];
    private readonly string $_identifier;
}
