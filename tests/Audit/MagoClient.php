<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

use JsonException;
use RuntimeException;

final class MagoClient
{
    private readonly FormatterRunFactory $formatterRuns;

    public function __construct(
        private readonly AuditPaths $paths,
        private readonly ProcessRunner $processes,
    ) {
        $this->formatterRuns = new FormatterRunFactory($paths->magoDirectory);
    }

    /**
     * @param list<string> $rules
     * @param list<string> $files
     * @return list<array{file: string, code: string, severity: int, type: string, line: int, column: int}>
     */
    public function lint(array $rules, array $files): array
    {
        if ($rules === [] || $files === []) {
            return [];
        }

        $command = [
            PHP_BINARY,
            $this->paths->magoBinary,
            '--config',
            $this->paths->magoConfiguration,
            'lint',
            '--only',
            implode(',', $rules),
            '--reporting-format=json',
            '--reporting-target=stdout',
            ...$files,
        ];
        $result = $this->processes->run($command, $this->paths->magoDirectory, allowedExitCodes: [0, 1, 2]);
        if (trim($result->stdout) === '') {
            return [];
        }

        try {
            /** @var array{issues?: list<array{level: string, code: string, annotations: list<array{kind: string, span: array{file_id: array{path: string}, start: array{offset: int, line: int}}}>}>} $report */
            $report = json_decode($result->stdout, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid Mago JSON report: ' . $exception->getMessage(), previous: $exception);
        }

        $diagnostics = [];
        foreach ($report['issues'] ?? [] as $issue) {
            if (!in_array($issue['code'], $rules, strict: true)) {
                continue;
            }

            $primary = $this->primaryAnnotation($issue['annotations']);
            if ($primary === null) {
                continue;
            }

            $file = $this->canonicalPath($primary['span']['file_id']['path']);
            $diagnostics[] = [
                'file' => $file,
                'code' => $issue['code'],
                'severity' => 5,
                'type' => $this->normalizeLevel($issue['level']),
                'line' => $primary['span']['start']['line'] + 1,
                'column' => $this->column($file, $primary['span']['start']['offset']),
            ];
        }

        return $diagnostics;
    }

    /** @param list<string> $files */
    public function format(array $files): FormatterRun
    {
        if ($files === []) {
            return new FormatterRun(0, []);
        }

        $result = $this->processes->run(
            [
                PHP_BINARY,
                $this->paths->magoBinary,
                '--config',
                $this->paths->magoConfiguration,
                'format',
                ...$files,
            ],
            $this->paths->magoDirectory,
            allowedExitCodes: [0, 1, 2],
        );

        return $this->formatterRuns->create($result, $files);
    }

    /**
     * @param list<array{kind: string, span: array{file_id: array{path: string}, start: array{offset: int, line: int}}}> $annotations
     * @return array{kind: string, span: array{file_id: array{path: string}, start: array{offset: int, line: int}}}|null
     */
    private function primaryAnnotation(array $annotations): ?array
    {
        foreach ($annotations as $annotation) {
            if ($annotation['kind'] === 'Primary') {
                return $annotation;
            }
        }

        return null;
    }

    private function column(string $file, int $offset): int
    {
        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new RuntimeException('Unable to read Mago source file: ' . $file);
        }

        $before = substr($contents, offset: 0, length: $offset);
        $lineStart = strrpos($before, needle: "\n");

        return $offset - ($lineStart === false ? 0 : $lineStart + 1) + 1;
    }

    private function normalizeLevel(string $level): string
    {
        return match ($level) {
            'Error' => 'ERROR',
            'Warning' => 'WARNING',
            default => strtoupper($level),
        };
    }

    private function canonicalPath(string $path): string
    {
        $canonical = realpath($path);

        return $canonical === false ? $path : $canonical;
    }
}
