<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules\FileHeader;

use Mago\Sdk\CancellationTokenInterface;
use Mago\Sdk\Span;
use Mago\Sdk\Syntax\SourceFile;

use function array_key_exists;
use function str_replace;
use function substr;
use function substr_count;

/**
 * Reports repeated header block types separated by another block type.
 *
 * @internal
 */
final class FileHeaderGroupingInspector
{
    public function __construct(
        private readonly SourceFile $file,
        private readonly CancellationTokenInterface $cancellation,
    ) {}

    /**
     * @param list<HeaderBlock> $blocks
     * @return list<array{message: string, span: Span}>
     */
    public function inspect(array $blocks): array
    {
        $firstBlocks = [];
        $issues = [];
        foreach ($blocks as $index => $block) {
            $this->cancellation->throwIfCancelled();
            if (!array_key_exists($block->type, $firstBlocks)) {
                $firstBlocks[$block->type] = $block;
            }

            $nextBlock = $blocks[$index + 1] ?? null;
            if (
                $nextBlock === null
                || $nextBlock->type === $block->type
                || !array_key_exists($nextBlock->type, $firstBlocks)
            ) {
                continue;
            }

            $firstBlock = $firstBlocks[$nextBlock->type];
            $issues[] = [
                'message' =>
                    'Similar statements must be grouped together inside header blocks; the first "'
                        . $nextBlock->type
                        . '" statement was found on line '
                        . $this->getLine($firstBlock->start),
                'span' => $nextBlock->getStartSpan(),
            ];
        }

        return $issues;
    }

    private function getLine(int $offset): int
    {
        $prefix = substr($this->file->contents, offset: 0, length: $offset);
        $normalized = str_replace(search: ["\r\n", "\r"], replace: "\n", subject: $prefix);

        return substr_count($normalized, needle: "\n") + 1;
    }
}
