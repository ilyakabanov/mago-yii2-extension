<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter;

use Mago\Sdk\Span;
use Mago\Sdk\Syntax\SourceFile;
use Mago\Sdk\Syntax\Trivia;

use function max;
use function min;
use function preg_match;
use function str_contains;
use function strlen;
use function strpos;
use function strrpos;
use function substr;

final class SourceLayout
{
    public function __construct(
        private readonly SourceFile $file,
    ) {}

    public function getTextBetween(int $start, int $end): string
    {
        return substr(string: $this->file->contents, offset: $start, length: $end - $start);
    }

    public function hasLineBreak(string $text): bool
    {
        return str_contains($text, "\n") || str_contains($text, "\r");
    }

    public function isSingleLineGap(string $text): bool
    {
        return preg_match(pattern: '/^[^\S\r\n]*(?:\r\n|\r|\n)[^\S\r\n]*$/D', subject: $text) === 1;
    }

    public function getIndent(int $offset): string
    {
        $before = substr(string: $this->file->contents, offset: 0, length: $offset);
        $lineFeed = strrpos(haystack: $before, needle: "\n");
        $carriageReturn = strrpos(haystack: $before, needle: "\r");
        $lineStart = 0;
        if ($lineFeed !== false) {
            $lineStart = $lineFeed + 1;
        }
        if ($carriageReturn !== false) {
            $lineStart = max($lineStart, $carriageReturn + 1);
        }

        return substr(string: $this->file->contents, offset: $lineStart, length: $offset - $lineStart);
    }

    public function getLineEnd(int $offset): int
    {
        $lineFeed = strpos(haystack: $this->file->contents, needle: "\n", offset: $offset);
        $carriageReturn = strpos(haystack: $this->file->contents, needle: "\r", offset: $offset);
        if ($lineFeed === false) {
            return $carriageReturn === false ? strlen($this->file->contents) : $carriageReturn;
        }
        if ($carriageReturn === false) {
            return $lineFeed;
        }

        return min($lineFeed, $carriageReturn);
    }

    public function findCharacterOutsideTrivia(string $character, int $start, int $end): ?Span
    {
        $offset = strpos(haystack: $this->file->contents, needle: $character, offset: $start);
        while ($offset !== false && $offset < $end) {
            $trivia = $this->findTriviaAt($offset);
            if ($trivia === null) {
                return new Span($offset, $offset + 1);
            }

            $offset = strpos(haystack: $this->file->contents, needle: $character, offset: $trivia->span->end);
        }

        return null;
    }

    public function getPreviousContentEnd(int $start, int $end): int
    {
        $previousContentEnd = $start;
        foreach ($this->file->getTrivia() as $trivia) {
            if ($trivia->span->start < $start || $trivia->span->end > $end) {
                continue;
            }

            $previousContentEnd = max($previousContentEnd, $trivia->span->end);
        }

        return $previousContentEnd;
    }

    public function findTriviaAt(int $offset): ?Trivia
    {
        foreach ($this->file->getTrivia() as $trivia) {
            if ($trivia->span->start <= $offset && $offset < $trivia->span->end) {
                return $trivia;
            }
        }

        return null;
    }
}
