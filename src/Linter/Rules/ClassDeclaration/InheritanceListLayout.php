<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules\ClassDeclaration;

use Ilyakabanov\MagoYii2\Linter\SourceLayout;
use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Syntax\Node;

use function count;
use function preg_match;

final class InheritanceListLayout
{
    public function __construct(
        private readonly LintContext $context,
        private readonly SourceLayout $layout,
        private readonly string $type,
        private readonly string $expectedIndent,
    ) {}

    /**
     * @param non-empty-list<Node> $items
     */
    public function lint(Node $keyword, array $items): void
    {
        $lastItem = $items[count($items) - 1];
        $isMultiLine = $this->layout->hasLineBreak($this->layout->getTextBetween(
            $keyword->span->end,
            $lastItem->span->end,
        ));
        if (!$isMultiLine) {
            $this->lintSingleLine($keyword, $items);

            return;
        }

        $this->lintMultiLine($keyword, $items);
    }

    /**
     * @param non-empty-list<Node> $items
     */
    private function lintSingleLine(Node $keyword, array $items): void
    {
        if ($this->layout->getTextBetween($keyword->span->end, $items[0]->span->start) !== ' ') {
            $this->context->report(Issue::new("Exactly one space is required after `{$this->type}`.", $items[0]->span));
        }

        foreach ($items as $index => $item) {
            if ($index === 0) {
                continue;
            }

            $previous = $items[$index - 1];
            if ($this->layout->getTextBetween($previous->span->end, $item->span->start) === ', ') {
                continue;
            }

            $this->context->report(Issue::new(
                "A single-line `{$this->type}` list requires one space after each comma.",
                $item->span,
            ));
        }
    }

    /**
     * @param non-empty-list<Node> $items
     */
    private function lintMultiLine(Node $keyword, array $items): void
    {
        $firstGap = $this->layout->getTextBetween($keyword->span->end, $items[0]->span->start);
        if (!$this->layout->isSingleLineGap($firstGap)) {
            $this->context->report(Issue::new(
                "The first item in a multi-line `{$this->type}` list must follow the keyword on the next line.",
                $items[0]->span,
            ));
        }

        if (
            $this->layout->isSingleLineGap($firstGap)
            && $this->layout->getIndent($items[0]->span->start) !== $this->expectedIndent
        ) {
            $this->context->report(Issue::new(
                "Multi-line `{$this->type}` items must be indented four spaces from the declaration.",
                $items[0]->span,
            ));
        }

        foreach ($items as $index => $item) {
            if ($index === 0) {
                continue;
            }

            $previous = $items[$index - 1];
            $gap = $this->layout->getTextBetween($previous->span->end, $item->span->start);
            $isNextLine = preg_match(pattern: '/^,[^\S\r\n]*(?:\r\n|\r|\n)[^\S\r\n]*$/D', subject: $gap) === 1;
            if (!$isNextLine) {
                $this->context->report(Issue::new(
                    "Each item in a multi-line `{$this->type}` list must be on its own line.",
                    $item->span,
                ));

                continue;
            }

            if ($this->layout->getIndent($item->span->start) === $this->expectedIndent) {
                continue;
            }

            $this->context->report(Issue::new(
                "Multi-line `{$this->type}` items must be indented four spaces from the declaration.",
                $item->span,
            ));
        }
    }
}
