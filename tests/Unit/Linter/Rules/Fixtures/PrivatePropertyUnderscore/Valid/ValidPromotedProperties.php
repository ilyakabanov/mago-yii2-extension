<?php

declare(strict_types=1);

namespace Fixtures\Valid;

class ValidPromotedProperties
{
    public function __construct(
        private string $_promotedProperty,
        public string $publicParam,
        string $regularParam,
    ) {
        unset($regularParam);
    }
}
