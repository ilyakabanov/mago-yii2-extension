<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

use function strtolower;

/**
 * Requires parentheses when instantiating a named or dynamic class.
 *
 * @api
 */
final class ClassInstantiationRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/class-instantiation',
            name: 'Class instantiation',
            description: 'Requires parentheses when instantiating a named or dynamic class.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::Instantiation],
        );
    }

    public function lint(LintContext $context): void
    {
        $newKeyword = null;

        foreach ($context->getChildren() as $child) {
            if ($child->kind === NodeKind::ArgumentList) {
                return;
            }

            if ($child->kind === NodeKind::Keyword && strtolower($context->file->getText($child)) === 'new') {
                $newKeyword = $child;
            }
        }

        if ($newKeyword === null) {
            return;
        }

        $context->report(Issue::new(
            'Parentheses must be used when instantiating a new class.',
            $newKeyword->span,
        )->withHelp('Add an empty argument list after the class name.'));
    }
}
