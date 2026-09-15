<?php

declare(strict_types=1);

namespace Fixture;

abstract class ValidMethodDeclaration
{
    abstract protected function load(): void;

    final public static function build(): void
    {
    }

    public function __invoke(): void
    {
    }

    public function _(): void
    {
    }

    final public static function canonicalOrder(): void
    {
    }
}
