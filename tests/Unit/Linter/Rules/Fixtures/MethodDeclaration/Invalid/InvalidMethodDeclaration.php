<?php

declare(strict_types=1);

namespace Fixture;

abstract class InvalidMethodDeclaration
{
    public function _legacy(): void
    {
    }

    public final function finalAfterVisibility(): void
    {
    }

    public abstract function abstractAfterVisibility(): void;

    static protected function staticBeforeVisibility(): void
    {
    }
}
