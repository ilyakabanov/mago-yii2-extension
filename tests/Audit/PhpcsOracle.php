<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

use JsonException;
use RuntimeException;

final class PhpcsOracle
{
    public function __construct(
        private readonly AuditPaths $paths,
        private readonly ProcessRunner $processes,
    ) {}

    /** @return list<string> */
    public function listSniffs(): array
    {
        $result = $this->processes->run([
            $this->paths->phpcsBinary,
            '-e',
            '--standard=' . $this->paths->phpcsStandard,
        ]);

        $matches = [];
        preg_match_all('/^  ([A-Za-z0-9.]+)$/m', $result->stdout, $matches);

        /** @var list<string> $sniffs */
        $sniffs = $matches[1];
        sort($sniffs);

        return $sniffs;
    }

    /**
     * @param list<string> $files
     * @return list<array{file: string, source: string, severity: int, type: string, line: int, column: int, fixable: bool}>
     */
    public function inspect(array $files, ?string $sniff = null): array
    {
        if ($files === []) {
            return [];
        }

        $command = [
            $this->paths->phpcsBinary,
            '-q',
            '--standard=' . $this->paths->phpcsStandard,
            '--ignore-annotations',
            '--report=json',
        ];
        if ($sniff !== null) {
            $command[] = '--sniffs=' . $sniff;
        }
        array_push($command, ...$files);

        $result = $this->processes->run($command, allowedExitCodes: [0, 1, 2, 3]);

        try {
            /** @var array{files: array<string, array{messages: list<array{source: string, severity: int, type: string, line: int, column: int, fixable: bool}>}>} $report */
            $report = json_decode($result->stdout, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid PHPCS JSON report: ' . $exception->getMessage(), previous: $exception);
        }

        $diagnostics = [];
        foreach ($report['files'] as $file => $data) {
            foreach ($data['messages'] as $message) {
                $diagnostics[] = [
                    'file' => $this->canonicalPath($file),
                    'source' => $message['source'],
                    'severity' => $message['severity'],
                    'type' => $message['type'],
                    'line' => $message['line'],
                    'column' => $message['column'],
                    'fixable' => $message['fixable'],
                ];
            }
        }

        return $diagnostics;
    }

    private function canonicalPath(string $path): string
    {
        $canonical = realpath($path);

        return $canonical === false ? $path : $canonical;
    }
}
