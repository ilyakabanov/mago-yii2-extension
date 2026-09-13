<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules\ClassDeclaration;

use Ilyakabanov\MagoYii2\Linter\SourceLayout;
use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\NodeKind;

use function in_array;
use function strtolower;

final class InheritanceLayout
{
    public function __construct(
        private readonly LintContext $context,
        private readonly SourceLayout $layout,
        private readonly Node $name,
        private readonly string $declarationIndent,
    ) {}

    /**
     * @param list<Node> $children
     */
    public function lint(array $children): void
    {
        foreach ($children as $index => $child) {
            if ($child->kind !== NodeKind::Extends && $child->kind !== NodeKind::Implements) {
                continue;
            }

            $this->lintClause($child, $children[$index - 1] ?? $this->name);
        }
    }

    private function lintClause(Node $clause, Node $previous): void
    {
        $children = $this->context->file->getChildren($clause);
        $keyword = $this->getDirectChild($children, NodeKind::Keyword);
        if ($keyword === null) {
            return;
        }

        $items = $this->getItems($children);
        if ($items === []) {
            return;
        }

        $type = strtolower($this->context->file->getText($keyword));
        $declarationGap = $this->layout->getTextBetween($this->name->span->end, $keyword->span->start);
        $isDeclarationLine = !$this->layout->hasLineBreak($declarationGap);
        if (!$isDeclarationLine) {
            $this->context->report(Issue::new(
                "The `{$type}` keyword must be on the class-like declaration line.",
                $keyword->span,
            ));
        }

        if ($isDeclarationLine && $this->layout->getTextBetween($previous->span->end, $keyword->span->start) !== ' ') {
            $this->context->report(Issue::new("Exactly one space is required before `{$type}`.", $keyword->span));
        }

        (new InheritanceListLayout($this->context, $this->layout, $type, $this->declarationIndent . '    '))->lint(
            $keyword,
            $items,
        );
    }

    /**
     * @param list<Node> $children
     *
     * @return list<Node>
     */
    private function getItems(array $children): array
    {
        $items = [];
        foreach ($children as $child) {
            if (!in_array(
                needle: $child->kind,
                haystack: [NodeKind::Identifier, NodeKind::QualifiedIdentifier, NodeKind::FullyQualifiedIdentifier],
                strict: true,
            )) {
                continue;
            }

            $items[] = $child;
        }

        return $items;
    }

    /**
     * @param list<Node> $children
     */
    private function getDirectChild(array $children, NodeKind $kind): ?Node
    {
        foreach ($children as $child) {
            if ($child->kind === $kind) {
                return $child;
            }
        }

        return null;
    }
}
