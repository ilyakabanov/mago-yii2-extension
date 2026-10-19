<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Parity;

use Mago\Sdk\Analyzer\FileAnalysisRequirement;
use Mago\Sdk\Analyzer\NodeAnalysisContext;
use Mago\Sdk\Analyzer\NodeAnalysisHook;
use Mago\Sdk\Analyzer\Plugin;
use Mago\Sdk\Analyzer\PluginDefinition;
use Mago\Sdk\Analyzer\PluginRegistry;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;
use Mago\Sdk\Analyzer\Type\NamedObjectType;

/** Test-only semantic comparisons, deliberately independent of displayed type strings. */
final class ParityPlugin implements Plugin, NodeAnalysisHook
{
    public function getDefinition(): PluginDefinition
    {
        return new PluginDefinition('yii2/parity', 'Original PHPStan expectations', 'Native semantic comparisons of the original type assertion corpus.');
    }

    public function register(PluginRegistry $registry): void
    {
        $registry->registerNodeAnalysisHook($this);
    }

    public function getTargets(): array
    {
        return [NodeKind::FunctionCall];
    }

    public function getRequirements(): array
    {
        return [FileAnalysisRequirement::ArgumentTypes, FileAnalysisRequirement::SourceText, FileAnalysisRequirement::TargetSubtree];
    }

    public function analyze(NodeAnalysisContext $context): void
    {
        if (preg_match('/^\\\\Parity\\\\(assertType|assertSuperType)\s*\(/', $context->source->getText($context->node), $match) !== 1) {
            return;
        }

        $actual = $context->argumentTypes[0] ?? null;
        if ($actual === null) {
            $argument = $context->source->getFirstDescendant($context->node, NodeKind::PositionalArgument);
            if ($argument !== null) {
                foreach ($context->source->getChildren($argument) as $expression) {
                    $actual = $context->analysis->getExpressionType($expression);
                    if ($actual !== null) { break; }
                }
            }
        }
        $expected = $context->argumentTypes[1] ?? null;
        $wrapper = $expected?->atomicTypes[0] ?? null;
        $expected = $wrapper instanceof NamedObjectType && strtolower($wrapper->name) === 'parity\\expectedtype'
            ? ($wrapper->parameters[0] ?? null) : null;
        if ($actual === null || $expected === null) {
            $context->report(Level::Error, 'missing-type', Issue::new('Parity comparison is missing inferred argument types.', $context->node->span));
            return;
        }

        $expected = ExpectedTypeResolver::resolve($expected, $context->codebase->classExists('yii\\log\\PsrMessage'));

        $passed = $match[1] === 'assertSuperType'
            ? $context->types->isContainedBy($actual, $expected)
            : ($context->types->isContainedBy($actual, $expected) && $context->types->isContainedBy($expected, $actual));
        $context->report(
            $passed ? Level::Note : Level::Error,
            $passed ? 'pass' : 'type',
            Issue::new(
                ($passed ? 'Matched' : 'Expected') . ' ' . TypeDescription::describe($expected) . '; actual ' . TypeDescription::describe($actual) . ' (' . $match[1] . ').',
                $context->node->span,
            ),
        );
    }
}
