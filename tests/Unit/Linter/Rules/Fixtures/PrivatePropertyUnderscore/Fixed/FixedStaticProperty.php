<?php

declare(strict_types=1);

namespace Fixtures\Invalid;

class InvalidStaticProperty
{
    private static ?self $_singletonInstance = null;
}
