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

use function array_intersect_key;
use function array_key_exists;
use function array_values;
use function preg_match;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtolower;

/**
 * Enforces Yii2 method names and modifier order.
 *
 * @api
 */
final class MethodDeclarationRule implements Rule
{
    private const VISIBILITY_MODIFIERS = [
        'public' => true,
        'protected' => true,
        'private' => true,
    ];

    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/method-declaration',
            name: 'Method declaration',
            description: 'Forbids visibility underscores and enforces method modifier order.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::Method],
        );
    }

    public function lint(LintContext $context): void
    {
        $methodNameNode = null;
        $modifiers = [];
        foreach ($context->getChildren() as $child) {
            if ($child->kind === NodeKind::LocalIdentifier) {
                $methodNameNode = $child;
            }

            if ($child->kind === NodeKind::Modifier) {
                $modifiers[strtolower($context->file->getText($child))] = $child;
            }
        }

        if ($methodNameNode === null) {
            return;
        }

        $this->lintVisibilityUnderscore($context, $methodNameNode);

        $visibilityModifiers = array_values(array_intersect_key($modifiers, self::VISIBILITY_MODIFIERS));
        $visibilityModifier = $visibilityModifiers[0] ?? null;
        if ($visibilityModifier === null) {
            return;
        }

        $this->lintPrecedingModifier($context, $modifiers, 'final', $visibilityModifier);
        $this->lintPrecedingModifier($context, $modifiers, 'abstract', $visibilityModifier);
        $this->lintStaticModifier($context, $modifiers, $visibilityModifier);
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

    /**
     * @param array<string, Node> $modifiers
     */
    private function lintPrecedingModifier(
        LintContext $context,
        array $modifiers,
        string $modifier,
        Node $visibilityModifier,
    ): void {
        if (!array_key_exists($modifier, $modifiers)) {
            return;
        }

        $modifierNode = $modifiers[$modifier];
        if ($modifierNode->span->start < $visibilityModifier->span->start) {
            return;
        }

        $context->report(Issue::new(
            "The `{$modifier}` modifier must precede method visibility.",
            $modifierNode->span,
        )->withHelp("Move `{$modifier}` before the visibility modifier."));
    }

    /**
     * @param array<string, Node> $modifiers
     */
    private function lintStaticModifier(LintContext $context, array $modifiers, Node $visibilityModifier): void
    {
        if (!array_key_exists(key: 'static', array: $modifiers)) {
            return;
        }

        $staticModifier = $modifiers['static'];
        if ($staticModifier->span->start > $visibilityModifier->span->start) {
            return;
        }

        $context->report(Issue::new(
            'The `static` modifier must follow method visibility.',
            $staticModifier->span,
        )->withHelp('Move `static` after the visibility modifier.'));
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
