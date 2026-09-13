<?php

declare(strict_types=1);

namespace Fixtures\Valid;

class ValidPhp84Properties
{
    public private(set) string $publicProperty = 'public';
    protected private(set) string $protectedProperty = 'protected';

    private string $_hookedProperty {
        get => $this->_hookedProperty;
        set => $value;
    }

    public string $publicHookedProperty {
        get => $this->publicHookedProperty;
        set => $value;
    }

    public function __construct(
        public private(set) string $promotedProperty,
    ) {}
}
