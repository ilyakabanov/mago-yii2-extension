<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

/**
 * Forbids a leading backslash in namespace import statements.
 *
 * @api
 */
final class ImportStatementRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/import-statement',
            name: 'Import statement',
            description: 'Forbids a leading backslash in namespace import statements.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::Use],
        );
    }

    public function lint(LintContext $context): void
    {
        $identifier = $context->file->getFirstDescendant($context->node, NodeKind::Identifier);
        if ($identifier === null) {
            return;
        }

        $identifierParts = $context->file->getChildren($identifier);
        $fullyQualifiedIdentifier = $identifierParts[0] ?? null;
        if (
            $fullyQualifiedIdentifier === null
            || $fullyQualifiedIdentifier->kind !== NodeKind::FullyQualifiedIdentifier
        ) {
            return;
        }

        $context->report(Issue::new(
            'Import statements must not begin with a leading backslash.',
            $fullyQualifiedIdentifier->span,
        )->withHelp('Remove the leading backslash from the import.'));
    }
}
