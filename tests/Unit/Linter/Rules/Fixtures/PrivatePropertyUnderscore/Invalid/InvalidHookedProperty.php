<?php

declare(strict_types=1);

namespace Fixtures\Invalid;

class InvalidHookedProperty
{
    private string $hookedProperty {
        get => $this->hookedProperty;
        set => $value;
    }
}
