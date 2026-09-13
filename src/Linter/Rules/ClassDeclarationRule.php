<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\ClassDeclaration\BraceLayout;
use Ilyakabanov\MagoYii2\Linter\Rules\ClassDeclaration\InheritanceLayout;
use Ilyakabanov\MagoYii2\Linter\SourceLayout;
use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\NodeKind;

use function max;

final class ClassDeclarationRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/class-declaration',
            name: 'Class declaration',
            description: 'Requires PSR-2 layout for named class-like declarations.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::Class_, NodeKind::Interface, NodeKind::Trait, NodeKind::Enum],
        );
    }

    public function lint(LintContext $context): void
    {
        $children = $context->getChildren();
        $keyword = $this->getDirectChild($children, NodeKind::Keyword);
        $name = $this->getDirectChild($children, NodeKind::LocalIdentifier);
        if ($keyword === null || $name === null) {
            return;
        }

        $layout = new SourceLayout($context->file);
        $declarationStart = $this->lintHeaderSpacing($context, $layout, $children, $keyword, $name);
        $declarationIndent = $layout->getIndent($declarationStart);

        (new InheritanceLayout($context, $layout, $name, $declarationIndent))->lint($children);
        (new BraceLayout($context, $layout, $declarationIndent))->lint($children, $this->getHeaderEnd(
            $children,
            $name,
        ));
    }

    /**
     * @param list<Node> $children
     */
    private function lintHeaderSpacing(
        LintContext $context,
        SourceLayout $layout,
        array $children,
        Node $keyword,
        Node $name,
    ): int {
        $lastModifier = null;
        foreach ($children as $child) {
            if ($child->span->start >= $keyword->span->start) {
                break;
            }

            if ($child->kind === NodeKind::Modifier) {
                $lastModifier = $child;
            }
        }

        $declarationStart = $lastModifier?->span->start ?? $keyword->span->start;
        if (
            $lastModifier !== null
            && $layout->getTextBetween($lastModifier->span->end, $keyword->span->start) !== ' '
        ) {
            $context->report(Issue::new(
                'Exactly one space is required between the class modifier and keyword.',
                $keyword->span,
            ));
        }

        if ($layout->getTextBetween($keyword->span->end, $name->span->start) !== ' ') {
            $context->report(Issue::new('Exactly one space is required after the class-like keyword.', $name->span));
        }

        return $declarationStart;
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

    /**
     * @param list<Node> $children
     */
    private function getHeaderEnd(array $children, Node $name): int
    {
        $headerEnd = $name->span->end;
        foreach ($children as $child) {
            if ($child->kind === NodeKind::ClassLikeMember) {
                continue;
            }

            $headerEnd = max($headerEnd, $child->span->end);
        }

        return $headerEnd;
    }
}
