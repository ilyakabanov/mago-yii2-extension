<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules\FileHeader;

use Mago\Sdk\Span;
use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\NodeKind;
use Mago\Sdk\Syntax\SourceFile;
use Mago\Sdk\Syntax\Trivia;

use function in_array;
use function strtolower;
use function substr;

/**
 * Converts header syntax nodes and trivia into semantic blocks.
 *
 * @internal
 */
final class HeaderBlockFactory
{
    public function __construct(
        private readonly SourceFile $file,
    ) {}

    public function createFromNode(Node $node, int $headerStart): ?HeaderBlock
    {
        if ($node->span->start < $headerStart) {
            return null;
        }

        if (
            $node->kind === NodeKind::Declare
            && substr($this->file->contents, offset: $node->span->end - 1, length: 1) === ';'
        ) {
            return new HeaderBlock(
                'declare',
                $node->span->start,
                $node->span->end,
                new Span($node->span->end - 1, $node->span->end),
            );
        }

        if ($node->kind === NodeKind::Use) {
            return new HeaderBlock(
                $this->getUseType($node),
                $node->span->start,
                $node->span->end,
                new Span($node->span->end - 1, $node->span->end),
            );
        }

        if ($node->kind !== NodeKind::Namespace) {
            return null;
        }

        $body = $this->file->getFirstDescendant($node, NodeKind::NamespaceImplicitBody);
        $terminator = $body === null ? null : $this->file->getFirstDescendant($body, NodeKind::Terminator);
        if ($terminator === null) {
            return null;
        }

        return new HeaderBlock('namespace', $node->span->start, $terminator->span->end, $terminator->span);
    }

    public function createFromDocblock(Trivia $trivia): HeaderBlock
    {
        return new HeaderBlock(
            'docblock',
            $trivia->span->start,
            $trivia->span->end,
            new Span($trivia->span->end - 2, $trivia->span->end),
        );
    }

    private function getUseType(Node $use): string
    {
        $items = $this->file->getFirstDescendant($use, NodeKind::UseItems);
        $container = $items === null ? null : $this->file->getChildren($items)[0] ?? null;
        if (
            $container === null
            || !in_array($container->kind, [NodeKind::TypedUseItemList, NodeKind::TypedUseItemSequence], strict: true)
        ) {
            return 'use';
        }

        $useType = $this->file->getFirstDescendant($container, NodeKind::UseType);
        if ($useType === null) {
            return 'use';
        }

        return 'use ' . strtolower($this->file->getText($useType));
    }
}
