<?php

declare(strict_types=1);

namespace Fixture;

abstract class ValidPhp84PropertyDeclaration
{
    abstract public string $abstractProperty { get; }
    final protected string $finalProperty { get => 'value'; }
    public private(set) string $asymmetricProperty = 'value';
    private(set) string $writeVisibilityOnly = 'value';
}
