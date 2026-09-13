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

use function strtolower;
use function trim;

/**
 * Requires explicit visibility on every class-like constant declaration.
 *
 * @api
 */
final class ConstantVisibilityRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/constant-visibility',
            name: 'Constant visibility',
            description: 'Requires explicit visibility on every class-like constant declaration.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::ClassLikeConstant],
        );
    }

    public function lint(LintContext $context): void
    {
        $constantKeyword = null;

        foreach ($context->getChildren() as $child) {
            if ($child->kind === NodeKind::Modifier && $this->isVisibilityModifier($context, $child)) {
                return;
            }

            if ($child->kind === NodeKind::Keyword && strtolower($context->file->getText($child)) === 'const') {
                $constantKeyword = $child;
            }
        }

        if ($constantKeyword === null) {
            return;
        }

        $context->report(Issue::new(
            'Visibility must be declared on class constants.',
            $constantKeyword->span,
        )->withHelp('Add `public`, `protected`, or `private` before the constant declaration.'));
    }

    private function isVisibilityModifier(LintContext $context, Node $modifier): bool
    {
        return match (strtolower(trim($context->file->getText($modifier)))) {
            'public', 'protected', 'private' => true,
            default => false,
        };
    }
}
