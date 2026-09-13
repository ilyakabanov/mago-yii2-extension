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

use function str_starts_with;
use function strtolower;
use function substr;
use function trim;

/**
 * Requires private property names to start with an underscore prefix ($_).
 *
 * According to Yii 2 code style, private properties must be named like $_varName.
 *
 * @api
 */
final class PrivatePropertyUnderscoreRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/private-property-underscore',
            name: 'Private property underscore',
            description: 'Requires private property names to start with an underscore prefix ($_).',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [
                NodeKind::PlainProperty,
                NodeKind::HookedProperty,
                NodeKind::FunctionLikeParameter,
            ],
        );
    }

    public function lint(LintContext $context): void
    {
        if ($context->node->kind === NodeKind::FunctionLikeParameter) {
            $this->lintPromotedParameter($context);
            return;
        }

        $this->lintProperty($context);
    }

    private function lintProperty(LintContext $context): void
    {
        $modifiers = $context->file->getDescendants($context->node, NodeKind::Modifier);
        if (!$this->hasPrivateModifier($context, $modifiers)) {
            return;
        }

        $propertyItems = $context->file->getDescendants($context->node, NodeKind::PropertyItem);
        foreach ($propertyItems as $item) {
            $varNode = $context->file->getFirstDescendant($item, NodeKind::DirectVariable);
            if ($varNode !== null) {
                $this->validateVariableName($context, $varNode, 'property');
            }
        }
    }

    private function lintPromotedParameter(LintContext $context): void
    {
        $modifiers = $context->file->getDescendants($context->node, NodeKind::Modifier);
        if (!$this->hasPrivateModifier($context, $modifiers)) {
            return;
        }

        $varNode = $context->file->getFirstDescendant($context->node, NodeKind::DirectVariable);
        if ($varNode !== null) {
            $this->validateVariableName($context, $varNode, 'promoted property');
        }
    }

    /**
     * @param list<Node> $modifiers
     */
    private function hasPrivateModifier(LintContext $context, array $modifiers): bool
    {
        foreach ($modifiers as $modifier) {
            $text = strtolower(trim($context->file->getText($modifier)));
            if ($text === 'private') {
                return true;
            }
        }

        return false;
    }

    private function validateVariableName(LintContext $context, Node $varNode, string $targetType): void
    {
        $varText = $context->file->getText($varNode);
        if (!str_starts_with($varText, '$')) {
            return;
        }

        if (str_starts_with($varText, '$_')) {
            return;
        }

        $varName = substr($varText, offset: 1);

        $context->report(Issue::new(
            "Private {$targetType} \${$varName} must start with an underscore prefix (\$_{$varName}).",
            $varNode->span,
        )->withHelp("Rename \${$varName} to \$_{$varName}."));
    }
}
