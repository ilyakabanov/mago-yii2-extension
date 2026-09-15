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

use function preg_match;
use function str_replace;
use function str_starts_with;
use function strlen;

/**
 * Forbids visibility underscores in method names.
 *
 * @api
 */
final class MethodDeclarationRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/method-declaration',
            name: 'Method declaration',
            description: 'Forbids visibility underscores in method names.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::Method],
        );
    }

    public function lint(LintContext $context): void
    {
        foreach ($context->getChildren() as $child) {
            if ($child->kind !== NodeKind::LocalIdentifier) {
                continue;
            }

            $this->lintVisibilityUnderscore($context, $child);
            return;
        }
    }

    private function lintVisibilityUnderscore(LintContext $context, Node $methodNameNode): void
    {
        $methodName = $context->file->getText($methodNameNode);
        if (!$this->hasVisibilityUnderscore($methodName)) {
            return;
        }

        if ($this->allowsVisibilityUnderscore($context->file->path)) {
            return;
        }

        $context->report(Issue::new(
            "Method name \"{$methodName}\" must not use an underscore to indicate visibility.",
            $methodNameNode->span,
        )->withHelp('Remove the leading underscore from the method name.'));
    }

    private function hasVisibilityUnderscore(string $methodName): bool
    {
        return strlen($methodName) > 1 && str_starts_with($methodName, '_') && !str_starts_with($methodName, '__');
    }

    private function allowsVisibilityUnderscore(string $path): bool
    {
        $path = str_replace(search: '\\', replace: '/', subject: $path);

        return preg_match('~(?:^|/)(?:test|tests)/.*(?:Cest|Test)\.php$~', $path) === 1;
    }
}
