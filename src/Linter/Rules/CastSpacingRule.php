<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

use function str_ends_with;
use function str_replace;
use function str_starts_with;

/**
 * Disallows spaces and tabs inside type casts.
 *
 * @api
 */
final class CastSpacingRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/cast-spacing',
            name: 'Cast spacing',
            description: 'Disallows spaces and tabs inside type casts.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::UnaryPrefixOperator],
        );
    }

    public function lint(LintContext $context): void
    {
        $operator = $context->getText();
        if (!str_starts_with($operator, '(') || !str_ends_with($operator, ')')) {
            return;
        }

        $expected = str_replace([' ', "\t"], replace: '', subject: $operator);
        if ($operator === $expected) {
            return;
        }

        $context->report(Issue::new(
            "Cast statements must not contain whitespace; expected \"{$expected}\" but found \"{$operator}\"",
            $context->node->span,
        ));
    }
}
