<?php

declare(strict_types=1);

namespace Fixtures\Valid;

class ValidPublicProtected
{
    public string $publicTitle = 'title';
    public static int $publicCount = 0;
    protected string $protectedDescription = 'desc';
    protected static ?string $protectedTag = null;
}
