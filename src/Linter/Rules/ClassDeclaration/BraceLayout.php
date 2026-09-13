<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules\ClassDeclaration;

use Ilyakabanov\MagoYii2\Linter\SourceLayout;
use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Span;
use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\NodeKind;

use function substr;
use function trim;

final class BraceLayout
{
    public function __construct(
        private readonly LintContext $context,
        private readonly SourceLayout $layout,
        private readonly string $declarationIndent,
    ) {}

    /**
     * @param list<Node> $children
     */
    public function lint(array $children, int $headerEnd): void
    {
        $openingBrace = $this->layout->findCharacterOutsideTrivia('{', $headerEnd, $this->context->node->span->end);
        if ($openingBrace === null) {
            return;
        }

        $this->lintOpening($openingBrace, $headerEnd);
        $this->lintClosing($children);
    }

    private function lintOpening(Span $openingBrace, int $headerEnd): void
    {
        $previousContentEnd = $this->layout->getPreviousContentEnd($headerEnd, $openingBrace->start);
        $gap = $this->layout->getTextBetween($previousContentEnd, $openingBrace->start);
        if (!$this->layout->isSingleLineGap($gap)) {
            $this->context->report(Issue::new(
                'The opening brace must be on the line after the class-like declaration.',
                $openingBrace,
            ));

            return;
        }

        $lineEnd = $this->layout->getLineEnd($openingBrace->end);
        $remainder = substr(
            string: $this->context->file->contents,
            offset: $openingBrace->end,
            length: $lineEnd - $openingBrace->end,
        );
        if (trim(string: $remainder, characters: " \t") !== '') {
            $this->context->report(Issue::new(
                'The opening brace must be the only content on its line.',
                $openingBrace,
            ));
        }

        if ($this->layout->getIndent($openingBrace->start) !== $this->declarationIndent) {
            $this->context->report(Issue::new(
                'The opening brace must be aligned with the class-like declaration.',
                $openingBrace,
            ));
        }
    }

    /**
     * @param list<Node> $children
     */
    private function lintClosing(array $children): void
    {
        $closingOffset = $this->context->node->span->end - 1;
        if (($this->context->file->contents[$closingOffset] ?? null) !== '}') {
            return;
        }

        $closingBrace = new Span($closingOffset, $closingOffset + 1);
        $lastMember = null;
        foreach ($children as $child) {
            if ($child->kind !== NodeKind::ClassLikeMember) {
                continue;
            }

            $lastMember = $child;
        }

        if ($lastMember !== null) {
            $previousContentEnd = $this->layout->getPreviousContentEnd($lastMember->span->end, $closingBrace->start);
            $gap = $this->layout->getTextBetween($previousContentEnd, $closingBrace->start);
            if (!$this->layout->isSingleLineGap($gap)) {
                $this->context->report(Issue::new(
                    'The closing brace must be on the line immediately after the class-like body.',
                    $closingBrace,
                ));
            }
        }

        $lineEnd = $this->layout->getLineEnd($closingBrace->end);
        $offset = $closingBrace->end;
        while ($offset < $lineEnd) {
            $character = $this->context->file->contents[$offset];
            if ($character === ' ' || $character === "\t" || $character === ';') {
                ++$offset;
                continue;
            }

            $trivia = $this->layout->findTriviaAt($offset);
            if ($trivia !== null) {
                $offset = $trivia->span->end;
                continue;
            }

            $this->context->report(Issue::new('The closing brace must be the only code on its line.', $closingBrace));

            break;
        }
    }
}
