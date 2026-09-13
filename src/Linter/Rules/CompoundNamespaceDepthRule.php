<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

use function substr_count;

/**
 * Limits the depth of names inside grouped namespace imports.
 *
 * @api
 */
final class CompoundNamespaceDepthRule implements Rule
{
    private const MAX_DEPTH = 2;

    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/compound-namespace-depth',
            name: 'Compound namespace depth',
            description: 'Limits names inside grouped imports to two namespace segments.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::MixedUseItemList, NodeKind::TypedUseItemList],
        );
    }

    public function lint(LintContext $context): void
    {
        $useItems = $context->file->getDescendants($context->node, NodeKind::UseItem);
        foreach ($useItems as $useItem) {
            $identifier = $context->file->getFirstDescendant($useItem, NodeKind::Identifier);
            if ($identifier === null) {
                continue;
            }

            $depth = substr_count(haystack: $context->file->getText($identifier), needle: '\\') + 1;
            if ($depth <= self::MAX_DEPTH) {
                continue;
            }

            $context->report(Issue::new(
                'Names inside grouped imports must not exceed two namespace segments.',
                $identifier->span,
            )->withHelp('Import the name separately or shorten its path inside the group.'));
        }
    }
}
