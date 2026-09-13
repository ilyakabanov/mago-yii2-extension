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

use function array_filter;
use function array_values;
use function count;

/**
 * Enforces the structural requirements for trait imports.
 *
 * @api
 */
final class TraitUseDeclarationRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/trait-use-declaration',
            name: 'Trait use declaration',
            description: 'Requires one trait per import and keeps trait imports together before other members.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::Class_, NodeKind::Trait, NodeKind::Enum, NodeKind::AnonymousClass],
        );
    }

    public function lint(LintContext $context): void
    {
        $hasTraitUse = false;
        $hasNonTraitMember = false;

        foreach ($context->getChildren() as $child) {
            if ($child->kind !== NodeKind::ClassLikeMember) {
                continue;
            }

            $traitUse = $this->getDirectTraitUse($context, $child);
            if ($traitUse === null) {
                $hasNonTraitMember = true;
                continue;
            }

            $this->lintTraitCount($context, $traitUse);

            if (!$hasNonTraitMember) {
                $hasTraitUse = true;
                continue;
            }

            if (!$hasTraitUse) {
                $this->reportLateTraitUse($context, $traitUse);
                $hasTraitUse = true;
                continue;
            }

            $this->reportSeparatedTraitUse($context, $traitUse);
        }
    }

    private function lintTraitCount(LintContext $context, Node $traitUse): void
    {
        $traits = array_values(array_filter(
            $context->file->getChildren($traitUse),
            static fn(Node $child): bool => $child->kind === NodeKind::Identifier,
        ));
        if (count($traits) <= 1) {
            return;
        }

        $context->report(Issue::new('Each trait must have its own `use` statement.', $traits[1]->span)->withHelp(
            'Split the traits into separate `use` statements.',
        ));
    }

    private function reportSeparatedTraitUse(LintContext $context, Node $traitUse): void
    {
        $context->report(Issue::new(
            'Trait imports must be grouped together before other class members.',
            $traitUse->span,
        )->withHelp('Move this trait import next to the preceding trait imports.'));
    }

    private function reportLateTraitUse(LintContext $context, Node $traitUse): void
    {
        $context->report(Issue::new(
            'Trait imports must be declared before other class members.',
            $traitUse->span,
        )->withHelp('Move this trait import to the beginning of the class body.'));
    }

    private function getDirectTraitUse(LintContext $context, Node $member): ?Node
    {
        $traitUse = $context->file->getFirstDescendant($member, NodeKind::TraitUse);

        return $traitUse?->parentId === $member->id ? $traitUse : null;
    }
}
