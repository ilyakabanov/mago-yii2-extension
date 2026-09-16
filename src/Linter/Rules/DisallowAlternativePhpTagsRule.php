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

use function assert;
use function preg_match;
use function strlen;
use function strpos;
use function substr;

/**
 * Rejects script-style and ASP-style PHP opening tags.
 *
 * @api
 */
final class DisallowAlternativePhpTagsRule implements Rule
{
    private const SCRIPT_OPEN_TAG_PATTERN = '`<script (?:[^>]+)?language=[\'"]?php[\'"]?(?:[^>]+)?>`i';

    private const SNIPPET_LENGTH = 40;

    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'yii2/disallow-alternative-php-tags',
            name: 'Disallow alternative PHP tags',
            description: 'Disallows script-style and ASP-style PHP opening tags.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::Inline],
        );
    }

    public function lint(LintContext $context): void
    {
        $content = $context->file->getText($context->node);

        $matches = [];
        if (preg_match(self::SCRIPT_OPEN_TAG_PATTERN, $content, $matches) === 1) {
            $openingTag = $matches[0];
            $offset = strpos(haystack: $content, needle: $openingTag);
            assert($offset !== false, description: 'A regular expression match must exist in the searched content.');

            $snippet = $this->buildSnippet($content, $openingTag, $offset);
            $start = $context->node->span->start + $offset;

            $context->report(Issue::new(
                "Script style opening tag used; expected \"<?php\" but found \"{$snippet}\"",
                new Span(start: $start, end: $start + strlen($openingTag)),
            )->withHelp('Replace the script-style opening tag with "<?php".'));

            return;
        }

        $aspShortTagOffset = strpos(haystack: $content, needle: '<%=');
        if ($aspShortTagOffset !== false) {
            $snippet = $this->buildSnippet($content, '<%=', $aspShortTagOffset);
            $start = $context->node->span->start + $aspShortTagOffset;

            $context->report(Issue::new(
                "Possible use of ASP style short opening tags detected; found: {$snippet}",
                new Span(start: $start, end: $start + 3),
            )->withHelp('Replace the ASP-style short opening tag with "<?=".'));

            return;
        }

        $aspTagOffset = strpos(haystack: $content, needle: '<%');
        if ($aspTagOffset === false) {
            return;
        }

        $snippet = $this->buildSnippet($content, '<%', $aspTagOffset);
        $start = $context->node->span->start + $aspTagOffset;

        $context->report(Issue::new(
            "Possible use of ASP style opening tags detected; found: {$snippet}",
            new Span(start: $start, end: $start + 2),
        )->withHelp('Replace the ASP-style opening tag with "<?php".'));
    }

    private function buildSnippet(string $content, string $openingTag, int $offset): string
    {
        $snippetOffset = $offset + strlen($openingTag);
        $snippet = substr($content, $snippetOffset, self::SNIPPET_LENGTH);
        if ((strlen($content) - $snippetOffset) > self::SNIPPET_LENGTH) {
            $snippet .= '...';
        }

        return $openingTag . $snippet;
    }
}
