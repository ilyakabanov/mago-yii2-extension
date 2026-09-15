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

use function str_starts_with;
use function strlen;

/**
 * Rejects byte order marks at the beginning of PHP files.
 *
 * @api
 */
final class ByteOrderMarkRule implements Rule
{
    private const BOM_DEFINITIONS = [
        "\xEF\xBB\xBF" => 'UTF-8',
        "\xFE\xFF" => 'UTF-16 (BE)',
        "\xFF\xFE" => 'UTF-16 (LE)',
    ];

    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/byte-order-mark',
            name: 'Byte order mark',
            description: 'Disallows byte order marks at the beginning of PHP files.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::Program],
        );
    }

    public function lint(LintContext $context): void
    {
        foreach (self::BOM_DEFINITIONS as $byteOrderMark => $encoding) {
            if (!str_starts_with($context->file->contents, $byteOrderMark)) {
                continue;
            }

            $context->report(Issue::new(
                "File contains {$encoding} byte order mark, which may corrupt your application.",
                new Span(start: 0, end: strlen($byteOrderMark)),
            )->withHelp('Remove the byte order mark from the beginning of the file.'));

            return;
        }
    }
}
