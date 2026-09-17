<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

final class FormatterRunFactory
{
    public function __construct(
        private readonly string $workingDirectory,
    ) {}

    /** @param list<string> $files */
    public function create(ProcessResult $result, array $files): FormatterRun
    {
        $matches = [];
        preg_match_all("/Failed to parse file '([^']+)'/", $result->stderr, $matches);
        $failedFiles = [];
        foreach ($matches[1] as $file) {
            $path = str_starts_with($file, DIRECTORY_SEPARATOR) ? $file : $this->workingDirectory . '/' . $file;
            $failedFiles[] = $this->canonicalPath($path);
        }
        if ($result->exitCode !== 0 && $failedFiles === []) {
            $failedFiles = $files;
        }

        return new FormatterRun($result->exitCode, $failedFiles);
    }

    private function canonicalPath(string $path): string
    {
        $canonical = realpath($path);

        return $canonical === false ? $path : $canonical;
    }
}
