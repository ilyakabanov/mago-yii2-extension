<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules\FileHeader;

use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\NodeKind;
use Mago\Sdk\Syntax\SourceFile;

use function count;
use function str_starts_with;
use function substr;
use function trim;
use function usort;

/**
 * Selects the PHP block that PHPCS treats as the file header.
 *
 * @internal
 */
final class FileHeaderLocator
{
    public function __construct(
        private readonly SourceFile $file,
        private readonly FileHeaderSyntax $syntax,
        private readonly HeaderCandidateBuilder $candidateBuilder,
    ) {}

    /**
     * @return null|array{openingTag: Node, misplaced: bool, blocks: list<HeaderBlock>}
     */
    public function locate(): ?array
    {
        $openingTags = $this->file->getNodes(NodeKind::OpeningTag);
        usort($openingTags, static fn(Node $left, Node $right): int => $left->span->start <=> $right->span->start);
        if ($openingTags === []) {
            return null;
        }

        foreach ($openingTags as $index => $openingTag) {
            $blocks = $this->candidateBuilder->build($openingTag);
            if (count($blocks) > 1) {
                return [
                    'openingTag' => $openingTag,
                    'misplaced' => $index > 0 || !$this->isFirstContent($openingTag),
                    'blocks' => $blocks,
                ];
            }

            $nextOpeningTag = $openingTags[$index + 1] ?? null;
            if (
                $nextOpeningTag !== null
                && !$this->syntax->hasObjectOrientedNodeBetween($openingTag->span->end, $nextOpeningTag->span->start)
            ) {
                continue;
            }

            if ($index > 0 || !$this->isFirstContent($openingTag)) {
                return null;
            }

            return [
                'openingTag' => $openingTag,
                'misplaced' => false,
                'blocks' => $blocks,
            ];
        }

        return null;
    }

    private function isFirstContent(Node $openingTag): bool
    {
        if ($openingTag->span->start === 0) {
            return true;
        }

        $prefix = substr($this->file->contents, offset: 0, length: $openingTag->span->start);

        return str_starts_with(trim($prefix), '#!');
    }
}
