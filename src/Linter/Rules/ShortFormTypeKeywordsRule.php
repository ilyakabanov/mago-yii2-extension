<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

use function str_replace;
use function strtolower;
use function trim;

/**
 * Requires short forms for boolean and integer casts.
 *
 * @api
 */
final class ShortFormTypeKeywordsRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/short-form-type-keywords',
            name: 'Short-form type keywords',
            description: 'Requires `(bool)` and `(int)` instead of long-form boolean and integer casts.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::UnaryPrefixOperator],
        );
    }

    public function lint(LintContext $context): void
    {
        $type = str_replace([' ', "\t"], replace: '', subject: $context->getText());
        $type = strtolower(trim($type, characters: '()'));
        $replacement = match ($type) {
            'boolean' => 'bool',
            'integer' => 'int',
            default => null,
        };

        if ($replacement === null) {
            return;
        }

        $context->report(Issue::new(
            "Long-form cast \"({$type})\" must use \"({$replacement})\".",
            $context->node->span,
        )->withHelp("Replace \"({$type})\" with \"({$replacement})\"."));
    }
}
