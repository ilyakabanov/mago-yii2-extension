<?php

declare(strict_types=1);

namespace Corpus\Properties;

/**
 * Demonstrates valid constructor promoted properties for PHP 8.0+.
 */
class ValidPromotedProperties
{
    public function __construct(
        private string $_promotedPrivate = 'valid_promoted',
        public string $publicParam = 'public',
        protected int $protectedParam = 0,
        string $regularParam = 'regular',
    ) {
        unset($regularParam);
    }

    public function getPromotedPrivate(): string
    {
        return $this->_promotedPrivate;
    }
}
