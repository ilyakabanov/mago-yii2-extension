<?php

declare(strict_types=1);

namespace Fixture;

abstract class InvalidPhp84PropertyDeclaration
{
    private(set) public string $readAfterWriteVisibility = 'value';
    public final string $finalAfterVisibility { get => 'value'; }
    public abstract string $abstractAfterVisibility { get; }
}
