<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\NodeKind;

use function array_intersect_key;
use function array_key_exists;
use function array_values;
use function count;
use function strtolower;

/**
 * Enforces the structural requirements for class property declarations.
 *
 * @api
 */
final class PropertyDeclarationRule implements Rule
{
    private const VISIBILITY_MODIFIERS = [
        'public' => true,
        'protected' => true,
        'private' => true,
        'public(set)' => true,
        'protected(set)' => true,
        'private(set)' => true,
    ];

    private const WRITE_VISIBILITY_MODIFIERS = [
        'public(set)' => true,
        'protected(set)' => true,
        'private(set)' => true,
    ];

    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/property-declaration',
            name: 'Property declaration',
            description: 'Requires explicit, ordered property modifiers and one property per declaration.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::PlainProperty, NodeKind::HookedProperty],
        );
    }

    public function lint(LintContext $context): void
    {
        $declarationParts = $this->getDeclarationParts($context);
        $propertyItems = $context->file->getDescendants($context->node, NodeKind::PropertyItem);
        $variable = $context->file->getFirstDescendant($context->node, NodeKind::DirectVariable);
        if ($variable === null) {
            return;
        }

        if (array_key_exists(key: 'var', array: $declarationParts)) {
            $context->report(Issue::new(
                'The `var` keyword must not be used to declare a property.',
                $declarationParts['var']->span,
            )->withHelp('Replace `var` with an explicit visibility modifier.'));
        }

        if (count($propertyItems) > 1) {
            $secondVariable = $context->file->getFirstDescendant($propertyItems[1], NodeKind::DirectVariable);
            $context->report(Issue::new(
                'Only one property may be declared per statement.',
                ($secondVariable ?? $propertyItems[1])->span,
            )->withHelp('Declare each property in a separate statement.'));
        }

        $visibilityModifiers = $this->getVisibilityModifiers($declarationParts);
        if ($visibilityModifiers === []) {
            $variableName = $context->file->getText($variable);
            $context->report(Issue::new(
                "Visibility must be declared on property \"{$variableName}\".",
                $variable->span,
            )->withHelp('Add `public`, `protected`, or `private` before the property declaration.'));

            return;
        }

        $this->lintVisibilityOrder($context, $visibilityModifiers);

        $firstVisibility = $visibilityModifiers[0];
        $lastVisibility = $visibilityModifiers[count($visibilityModifiers) - 1];
        $this->lintPrecedingModifier($context, $declarationParts, 'final', $firstVisibility);
        $this->lintPrecedingModifier($context, $declarationParts, 'abstract', $firstVisibility);
        $this->lintFollowingModifier($context, $declarationParts, 'static', $lastVisibility);
        $this->lintFollowingModifier($context, $declarationParts, 'readonly', $lastVisibility);
    }

    /**
     * @return array<string, Node>
     */
    private function getDeclarationParts(LintContext $context): array
    {
        $declarationParts = [];
        foreach ($context->getChildren() as $child) {
            if ($child->kind !== NodeKind::Keyword && $child->kind !== NodeKind::Modifier) {
                continue;
            }

            $declarationParts[strtolower($context->file->getText($child))] = $child;
        }

        return $declarationParts;
    }

    /**
     * @param array<string, Node> $declarationParts
     *
     * @return list<Node>
     */
    private function getVisibilityModifiers(array $declarationParts): array
    {
        return array_values(array_intersect_key($declarationParts, self::VISIBILITY_MODIFIERS));
    }

    /**
     * @param list<Node> $visibilityModifiers
     */
    private function lintVisibilityOrder(LintContext $context, array $visibilityModifiers): void
    {
        if (count($visibilityModifiers) <= 1) {
            return;
        }

        $firstVisibility = $visibilityModifiers[0];
        $firstVisibilityName = strtolower($context->file->getText($firstVisibility));
        if (!array_key_exists($firstVisibilityName, self::WRITE_VISIBILITY_MODIFIERS)) {
            return;
        }

        $context->report(Issue::new(
            'Read visibility must be declared before write visibility on a property.',
            $visibilityModifiers[1]->span,
        )->withHelp('Move the read visibility before the write visibility.'));
    }

    /**
     * @param array<string, Node> $declarationParts
     */
    private function lintPrecedingModifier(
        LintContext $context,
        array $declarationParts,
        string $modifier,
        Node $firstVisibility,
    ): void {
        if (!array_key_exists($modifier, $declarationParts)) {
            return;
        }

        $modifierNode = $declarationParts[$modifier];
        if ($modifierNode->span->start < $firstVisibility->span->start) {
            return;
        }

        $context->report(Issue::new(
            "The `{$modifier}` modifier must precede property visibility.",
            $modifierNode->span,
        )->withHelp("Move `{$modifier}` before the visibility modifiers."));
    }

    /**
     * @param array<string, Node> $declarationParts
     */
    private function lintFollowingModifier(
        LintContext $context,
        array $declarationParts,
        string $modifier,
        Node $lastVisibility,
    ): void {
        if (!array_key_exists($modifier, $declarationParts)) {
            return;
        }

        $modifierNode = $declarationParts[$modifier];
        if ($modifierNode->span->start > $lastVisibility->span->start) {
            return;
        }

        $context->report(Issue::new(
            "The `{$modifier}` modifier must follow property visibility.",
            $modifierNode->span,
        )->withHelp("Move `{$modifier}` after the visibility modifiers."));
    }
}
