<?php

declare(strict_types=1);

namespace Fixtures\Valid;

class ValidPromotedProperties
{
    public function __construct(
        private string $_promotedProperty,
        private readonly string $_readonlyPromotedProperty,
        public string $publicParam,
        protected string $protectedParam,
        string $regularParam,
    ) {
        unset($regularParam);
    }
}
