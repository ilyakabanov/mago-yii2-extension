<?php

declare(strict_types=1);

namespace Fixtures\Valid;

class ValidPublicProtected
{
    public string $publicTitle = 'title';
    public static int $publicCount = 0;
    public string $_prefixedPublicTitle = 'title';
    protected string $protectedDescription = 'desc';
    protected static ?string $protectedTag = null;
    protected string $_prefixedProtectedDescription = 'desc';
}
