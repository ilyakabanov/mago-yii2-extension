<?php

declare(strict_types=1);

namespace Fixture;

const GLOBAL_CONSTANT = 1;

final class ValidConstantVisibility
{
    public const PUBLIC_CONSTANT = 1;
    protected const PROTECTED_CONSTANT = 2;
    private const PRIVATE_CONSTANT = 3;
    final public const FINAL_CONSTANT = 4;
}
