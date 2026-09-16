<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules\FileHeader;

use Mago\Sdk\CancellationTokenInterface;
use Mago\Sdk\Span;
use Mago\Sdk\Syntax\SourceFile;

use function in_array;
use function str_replace;
use function strlen;
use function strspn;
use function substr;
use function substr_count;

/**
 * Reports spacing diagnostics that the Mago formatter leaves unresolved.
 *
 * @internal
 */
final class FileHeaderSpacingInspector
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
        $issues = [];
        foreach ($blocks as $index => $block) {
            $this->cancellation->throwIfCancelled();
            $nextOffset = $block->end + strspn($this->file->contents, characters: " \t\r\n\v\f", offset: $block->end);
            if ($nextOffset >= strlen($this->file->contents)) {
                continue;
            }

            $lineGap = $this->getLine($nextOffset) - $this->getLine($block->end);
            if ($block->type === 'docblock' && $lineGap !== 2) {
                $issues[] = [
                    'message' => 'Header blocks must be separated by a single blank line',
                    'span' => $block->endSpan,
                ];
            }

            $nextBlock = $blocks[$index + 1] ?? null;
            if (
                $nextBlock !== null
                && in_array($block->type, ['declare', 'namespace'], strict: true)
                && $nextBlock->type === $block->type
                && $lineGap > 1
            ) {
                $issues[] = [
                    'message' => 'Header blocks must not contain blank lines',
                    'span' => $block->endSpan,
                ];
            }
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
