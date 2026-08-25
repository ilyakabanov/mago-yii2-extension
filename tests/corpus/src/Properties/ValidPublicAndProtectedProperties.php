<?php

declare(strict_types=1);

namespace Corpus\Properties;

/**
 * Demonstrates valid public and protected properties that must not trigger private property rules.
 */
class ValidPublicAndProtectedProperties
{
    public string $publicTitle = 'title';
    public static int $publicCounter = 0;
    protected string $protectedDescription = 'desc';
    protected static ?string $protectedTag = null;
}
