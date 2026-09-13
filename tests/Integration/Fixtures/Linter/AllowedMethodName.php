<?php

namespace Fixture;

final class AllowedMethodNameTest
{
    public function _helper(): void
    {
    }

    public final function misplacedModifier(): void
    {
    }
}
