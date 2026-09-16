<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Linter\Rules\FileHeader;

use Mago\Sdk\CancellationTokenInterface;
use Mago\Sdk\Span;
use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\SourceFile;

/**
 * Inspects the file-level layout represented by a Program node.
 *
 * @internal
 */
final class FileHeaderInspector
{
    public function __construct(
        private readonly SourceFile $file,
        private readonly Node $program,
        private readonly CancellationTokenInterface $cancellation,
    ) {}

    /**
     * @return list<array{message: string, span: Span}>
     */
    public function inspect(): array
    {
        $syntax = new FileHeaderSyntax($this->file, $this->program, $this->cancellation);
        $candidateBuilder = new HeaderCandidateBuilder(
            $this->file,
            $syntax,
            new HeaderBlockFactory($this->file),
            new FileDocblockClassifier($this->file),
            $this->cancellation,
        );
        $header = (new FileHeaderLocator($this->file, $syntax, $candidateBuilder))->locate();
        if ($header === null) {
            return [];
        }

        $issues = [];
        if ($header['misplaced']) {
            $issues[] = [
                'message' => 'The file header must be the first content in the file',
                'span' => $header['openingTag']->span,
            ];
        }

        return [
            ...$issues,
            ...(new FileHeaderSpacingInspector($this->file, $this->cancellation))->inspect($header['blocks']),
            ...(new FileHeaderGroupingInspector($this->file, $this->cancellation))->inspect($header['blocks']),
            ...(new FileHeaderOrderInspector($this->cancellation))->inspect($header['blocks']),
        ];
    }
}
