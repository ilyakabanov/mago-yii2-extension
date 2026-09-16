<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules\FileHeader;

use Mago\Sdk\Span;
use Mago\Sdk\Syntax\NodeKind;
use Mago\Sdk\Syntax\SourceFile;

use function in_array;
use function preg_match;

/**
 * Distinguishes a file-level docblock from documentation attached to code.
 *
 * @internal
 */
final class FileDocblockClassifier
{
    public function __construct(
        private readonly SourceFile $file,
    ) {}

    public function isFileDocblock(Span $docblock): bool
    {
        $nextKind = null;
        $nextStart = null;
        foreach ($this->file->getNodes() as $node) {
            if ($node->span->start < $docblock->end || !$this->isRelevant($node->kind)) {
                continue;
            }

            if ($nextStart === null) {
                $nextKind = $node->kind;
                $nextStart = $node->span->start;
                continue;
            }

            if ($node->span->start > $nextStart) {
                continue;
            }

            if ($node->span->start === $nextStart && $nextKind !== NodeKind::ExpressionStatement) {
                continue;
            }

            $nextKind = $node->kind;
            $nextStart = $node->span->start;
        }

        if (in_array(
            $nextKind,
            [
                NodeKind::Class_,
                NodeKind::Interface,
                NodeKind::Trait,
                NodeKind::Enum,
                NodeKind::Function,
                NodeKind::Closure,
                NodeKind::Switch,
                NodeKind::Match,
            ],
            strict: true,
        )) {
            return false;
        }

        return preg_match('/(?:\/\*\*|\R)\s*\*?\s*@var\b/i', $this->file->getText($docblock)) !== 1;
    }

    private function isRelevant(NodeKind $kind): bool
    {
        return in_array(
            $kind,
            [
                NodeKind::Declare,
                NodeKind::Namespace,
                NodeKind::Use,
                NodeKind::Class_,
                NodeKind::Interface,
                NodeKind::Trait,
                NodeKind::Enum,
                NodeKind::Function,
                NodeKind::Closure,
                NodeKind::Switch,
                NodeKind::Match,
                NodeKind::ExpressionStatement,
                NodeKind::Constant,
            ],
            strict: true,
        );
    }
}
