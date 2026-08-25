<?php

declare(strict_types=1);

namespace Fixtures\Invalid;

class InvalidPromotedProperty
{
    public function __construct(
        private string $_promotedParam,
        public string $publicParam,
    ) {}
}
