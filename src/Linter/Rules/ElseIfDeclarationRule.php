<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

use function substr;
use function trim;

/**
 * Requires the single `elseif` keyword instead of `else if`.
 *
 * @api
 */
final class ElseIfDeclarationRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/else-if-declaration',
            name: 'Else-if declaration',
            description: 'Requires the single `elseif` keyword instead of `else if`.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::IfStatementBodyElseClause],
        );
    }

    public function lint(LintContext $context): void
    {
        $children = $context->getChildren();
        $elseKeyword = $children[0] ?? null;
        $statement = $children[1] ?? null;
        if (
            $elseKeyword === null
            || $elseKeyword->kind !== NodeKind::Keyword
            || $statement === null
            || $statement->kind !== NodeKind::Statement
        ) {
            return;
        }

        $statementChildren = $context->file->getChildren($statement);
        $ifStatement = $statementChildren[0] ?? null;
        if ($ifStatement === null || $ifStatement->kind !== NodeKind::If) {
            return;
        }

        $separator = substr(
            string: $context->file->contents,
            offset: $elseKeyword->span->end,
            length: $ifStatement->span->start - $elseKeyword->span->end,
        );
        if (trim($separator) !== '') {
            return;
        }

        $context->report(Issue::new(
            'Usage of "else if" is discouraged; use "elseif" instead.',
            $elseKeyword->span,
        )->withHelp('Replace "else if" with "elseif".'));
    }
}
