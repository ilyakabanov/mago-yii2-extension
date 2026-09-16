<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules\FileHeader;

use Mago\Sdk\Span;

/**
 * One semantic block in a PSR-12 file header.
 *
 * @internal
 */
final class HeaderBlock
{
    public function __construct(
        public readonly string $type,
        public readonly int $start,
        public readonly int $end,
        public readonly Span $endSpan,
    ) {}

    public function getStartSpan(): Span
    {
        $length = match ($this->type) {
            'docblock', 'use', 'use function', 'use const' => 3,
            'declare' => 7,
            'namespace' => 9,
            default => $this->end - $this->start,
        };

        return new Span($this->start, $this->start + $length);
    }
}
