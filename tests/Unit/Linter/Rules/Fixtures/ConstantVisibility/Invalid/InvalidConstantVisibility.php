<?php

declare(strict_types=1);

namespace Fixture;

final class InvalidConstantVisibility
{
    const CLASS_CONSTANT = 1;
    final const FINAL_CONSTANT = 2;
}
