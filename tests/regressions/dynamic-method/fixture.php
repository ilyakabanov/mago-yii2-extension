<?php

declare(strict_types=1);

namespace Regression\DynamicMethod;

use function Mago\confirm;

final class Model
{
    public static function known(): int
    {
        return 1;
    }
}

function unknownMethod(string $method): void
{
    confirm(Model::$method(), 'mixed');
}

function knownMethod(): void
{
    confirm(Model::known(), 'int');
}
