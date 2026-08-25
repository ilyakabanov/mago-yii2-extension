<?php

declare(strict_types=1);

namespace Fixtures\Invalid;

class InvalidPromotedProperty
{
    public function __construct(
        private string $promotedParam,
        public string $publicParam,
    ) {}
}
