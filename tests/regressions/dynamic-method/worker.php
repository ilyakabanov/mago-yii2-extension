<?php

declare(strict_types=1);

use Mago\Sdk\Analyzer\FileAnalysisRequirement;
use Mago\Sdk\Analyzer\NodeAnalysisContext;
use Mago\Sdk\Analyzer\NodeAnalysisHook;
use Mago\Sdk\Analyzer\Plugin;
use Mago\Sdk\Analyzer\PluginDefinition;
use Mago\Sdk\Analyzer\PluginRegistry;
use Mago\Sdk\Extension;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;
use Mago\Sdk\Worker;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$probe = new class implements Plugin, NodeAnalysisHook {
    public function getDefinition(): PluginDefinition
    {
        return new PluginDefinition('regression/native-type', 'Native type evidence', 'Reports unmodified host types.');
    }

    public function register(PluginRegistry $registry): void
    {
        $registry->registerNodeAnalysisHook($this);
    }

    public function getTargets(): array
    {
        return [NodeKind::StaticMethodCall];
    }

    public function getRequirements(): array
    {
        return [
            FileAnalysisRequirement::TargetExpressionTypes,
            FileAnalysisRequirement::TargetSubtree,
        ];
    }

    public function analyze(NodeAnalysisContext $context): void
    {
        $lazy = $context->analysis->getExpressionType($context->node);
        $exactRecords = 0;
        foreach ($context->analysis->getAllExpressionTypes() as $expression) {
            if (
                $expression->span->start === $context->node->span->start
                && $expression->span->end === $context->node->span->end
            ) {
                $exactRecords++;
            }
        }

        $context->report(
            Level::Note,
            'evidence',
            Issue::new(
                'target='
                . ($context->targetType === null ? '<missing>' : (string) $context->targetType)
                . '; lazy='
                . ($lazy === null ? '<missing>' : (string) $lazy)
                . '; exact expression records='
                . $exactRecords,
                $context->node->span,
            ),
        );
    }
};

(new Worker(new Extension(
    identifier: 'regression/native-type',
    name: 'Native type evidence',
    version: '0.1.0',
    analyzerPlugins: [$probe],
)))->run();
