<?php

declare(strict_types=1);

namespace App;

final class Example
{
    private int $_value = 1;

    public function value(): int
    {
        return $this->_value;
    }
}
