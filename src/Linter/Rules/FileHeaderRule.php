<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules;

use Ilyakabanov\MagoYii2\Linter\Rules\FileHeader\FileHeaderInspector;
use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;

/**
 * Enforces the non-formatter parts of the PSR-12 file header layout.
 *
 * @api
 */
final class FileHeaderRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/file-header',
            name: 'File header',
            description: 'Enforces the order, grouping, and residual spacing of file header blocks.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::Program],
        );
    }

    public function lint(LintContext $context): void
    {
        $inspector = new FileHeaderInspector($context->file, $context->node, $context->cancellation);
        foreach ($inspector->inspect() as $descriptor) {
            $context->report(Issue::new($descriptor['message'], $descriptor['span']));
        }
    }
}
