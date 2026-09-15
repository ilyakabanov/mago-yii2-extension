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

    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/property-declaration',
            name: 'Property declaration',
            description: 'Requires explicit property visibility and one property per declaration.',
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

        if (array_intersect_key($declarationParts, self::VISIBILITY_MODIFIERS) !== []) {
            return;
        }

        $variableName = $context->file->getText($variable);
        $context->report(Issue::new(
            "Visibility must be declared on property \"{$variableName}\".",
            $variable->span,
        )->withHelp('Add `public`, `protected`, or `private` before the property declaration.'));
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
}
