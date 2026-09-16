<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules\FileHeader;

use Mago\Sdk\CancellationTokenInterface;
use Mago\Sdk\Span;
use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\NodeKind;
use Mago\Sdk\Syntax\SourceFile;

use function in_array;
use function usort;

/**
 * Exposes the logical top-level event stream needed by the file-header rule.
 *
 * @internal
 */
final class FileHeaderSyntax
{
    public function __construct(
        private readonly SourceFile $file,
        private readonly Node $program,
        private readonly CancellationTokenInterface $cancellation,
    ) {}

    /**
     * @return list<Node>
     */
    public function getTopLevelEvents(): array
    {
        $events = [];
        foreach ($this->file->getChildren($this->program) as $statement) {
            $this->cancellation->throwIfCancelled();
            if ($statement->kind !== NodeKind::Statement) {
                continue;
            }

            $event = $this->file->getChildren($statement)[0] ?? null;
            if ($event === null) {
                continue;
            }

            $events[] = $event;
            if ($event->kind !== NodeKind::Namespace) {
                continue;
            }

            $body = $this->file->getFirstDescendant($event, NodeKind::NamespaceImplicitBody);
            if ($body === null) {
                continue;
            }

            foreach ($this->file->getChildren($body) as $namespaceStatement) {
                if ($namespaceStatement->kind !== NodeKind::Statement) {
                    continue;
                }

                $namespaceEvent = $this->file->getChildren($namespaceStatement)[0] ?? null;
                if ($namespaceEvent !== null) {
                    $events[] = $namespaceEvent;
                }
            }
        }

        usort($events, static fn(Node $left, Node $right): int => $left->span->start <=> $right->span->start);

        return $events;
    }

    public function hasObjectOrientedNodeBetween(int $start, int $end): bool
    {
        $range = new Span($start, $end);
        foreach ($this->file->getNodes() as $node) {
            $nodeStart = new Span($node->span->start, $node->span->start + 1);
            if (
                $range->contains($nodeStart)
                && in_array(
                    $node->kind,
                    [
                        NodeKind::Class_,
                        NodeKind::Interface,
                        NodeKind::Trait,
                        NodeKind::Enum,
                        NodeKind::AnonymousClass,
                    ],
                    strict: true,
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
