<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Span;
use Mago\Sdk\Syntax\NodeKind;

use function strcspn;
use function strlen;
use function strspn;
use function substr;

/**
 * Disallows comments after declaration closing braces on the same line.
 *
 * @api
 */
final class ClosingBraceRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/closing-brace',
            name: 'Closing brace',
            description: 'Disallows comments after declaration closing braces on the same line.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [
                NodeKind::Class_,
                NodeKind::Interface,
                NodeKind::Trait,
                NodeKind::Enum,
                NodeKind::Function,
                NodeKind::Method,
            ],
        );
    }

    public function lint(LintContext $context): void
    {
        $contents = $context->file->contents;
        $nodeEnd = $context->node->span->end;
        if ($nodeEnd === 0 || substr($contents, offset: $nodeEnd - 1, length: 1) !== '}') {
            return;
        }

        $lineSuffix = substr(
            $contents,
            offset: $nodeEnd,
            length: strcspn($contents, characters: "\r\n", offset: $nodeEnd),
        );
        $commentOffset = $nodeEnd + strspn($lineSuffix, characters: " \t\v\f");
        if ($commentOffset === ($nodeEnd + strlen($lineSuffix))) {
            return;
        }

        foreach ($context->file->getTrivia() as $trivia) {
            if ($trivia->span->start !== $commentOffset) {
                continue;
            }

            $context->report(Issue::new(
                'Closing brace must not be followed by any comment or statement on the same line',
                new Span($nodeEnd - 1, $nodeEnd),
            ));

            return;
        }
    }
}
