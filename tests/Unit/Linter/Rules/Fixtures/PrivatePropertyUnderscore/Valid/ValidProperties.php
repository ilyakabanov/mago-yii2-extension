<?php

declare(strict_types=1);

namespace Fixtures\Valid;

class ValidProperties
{
    private string $_name = 'valid';
    private static ?self $_singleton = null;
    private readonly string $_secret;

    public function __construct(string $secret)
    {
        $this->_secret = $secret;
    }
}
