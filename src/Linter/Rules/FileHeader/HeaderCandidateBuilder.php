<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules\FileHeader;

use Mago\Sdk\CancellationTokenInterface;
use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\SourceFile;
use Mago\Sdk\Syntax\TriviaKind;

/**
 * Collects the PSR-12 header candidate that starts at one PHP opening tag.
 *
 * @internal
 */
final class HeaderCandidateBuilder
{
    public function __construct(
        private readonly SourceFile $file,
        private readonly FileHeaderSyntax $syntax,
        private readonly HeaderBlockFactory $blockFactory,
        private readonly FileDocblockClassifier $docblockClassifier,
        private readonly CancellationTokenInterface $cancellation,
    ) {}

    /**
     * @return list<HeaderBlock>
     */
    public function build(Node $openingTag): array
    {
        $blocks = [new HeaderBlock('tag', $openingTag->span->start, $openingTag->span->end, $openingTag->span)];
        $foundDocblock = false;
        $cursor = $openingTag->span->end;

        foreach ($this->syntax->getTopLevelEvents() as $event) {
            $this->cancellation->throwIfCancelled();
            if ($event->span->start < $cursor) {
                continue;
            }

            foreach ($this->file->getTrivia() as $trivia) {
                $this->cancellation->throwIfCancelled();
                if (
                    $trivia->kind !== TriviaKind::DocBlockComment
                    || $trivia->span->start < $cursor
                    || $trivia->span->start >= $event->span->start
                ) {
                    continue;
                }

                if ($foundDocblock || !$this->docblockClassifier->isFileDocblock($trivia->span)) {
                    return $blocks;
                }

                $blocks[] = $this->blockFactory->createFromDocblock($trivia);
                $foundDocblock = true;
                $cursor = $trivia->span->end;
            }

            $block = $this->blockFactory->createFromNode($event, $openingTag->span->end);
            if ($block === null) {
                break;
            }

            $blocks[] = $block;
            $cursor = $block->end;
        }

        return $blocks;
    }
}
