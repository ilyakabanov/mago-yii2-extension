<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules\FileHeader;

use Mago\Sdk\CancellationTokenInterface;
use Mago\Sdk\Span;

use function array_key_exists;
use function array_keys;
use function array_search;

/**
 * Reports file header block types that violate the canonical PSR-12 order.
 *
 * @internal
 */
final class FileHeaderOrderInspector
{
    /**
     * @var array<string, string>
     */
    private const BLOCK_LABELS = [
        'tag' => 'opening PHP tag',
        'docblock' => 'file-level docblock',
        'declare' => 'declare statements',
        'namespace' => 'namespace declaration',
        'use' => 'class-based use imports',
        'use function' => 'function-based use imports',
        'use const' => 'constant-based use imports',
    ];

    public function __construct(
        private readonly CancellationTokenInterface $cancellation,
    ) {}

    /**
     * @param list<HeaderBlock> $blocks
     * @return list<array{message: string, span: Span}>
     */
    public function inspect(array $blocks): array
    {
        $firstBlocks = [];
        foreach ($blocks as $block) {
            if (array_key_exists($block->type, $firstBlocks)) {
                continue;
            }

            $firstBlocks[$block->type] = $block;
        }

        $order = array_keys(self::BLOCK_LABELS);
        $currentIndex = 0;
        $issues = [];
        foreach ($firstBlocks as $type => $block) {
            $this->cancellation->throwIfCancelled();
            $typeIndex = array_search($type, $order, strict: true);
            if ($typeIndex === false || $typeIndex === 0) {
                continue;
            }

            if ($typeIndex > $currentIndex) {
                $currentIndex = $typeIndex;
                continue;
            }

            $previousType = 'tag';
            for ($index = 1; $index < $typeIndex; ++$index) {
                if (!array_key_exists($order[$index], $firstBlocks)) {
                    continue;
                }

                $previousType = $order[$index];
            }

            $issues[] = [
                'message' =>
                    'The '
                        . self::BLOCK_LABELS[$type]
                        . ' must follow the '
                        . self::BLOCK_LABELS[$previousType]
                        . ' in the file header',
                'span' => $block->getStartSpan(),
            ];
            $currentIndex = $typeIndex;
        }

        return $issues;
    }
}
