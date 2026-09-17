<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Tests\Audit;

use RuntimeException;

final class ProcessRunner
{
    /**
     * @param list<string> $command
     * @param string|null $workingDirectory
     * @param list<int> $allowedExitCodes
     */
    public function run(array $command, ?string $workingDirectory = null, array $allowedExitCodes = [0]): ProcessResult
    {
        if ($workingDirectory === '') {
            throw new RuntimeException('Working directory must not be empty.');
        }

        $pipes = [];
        $process = proc_open(
            command: $command,
            descriptor_spec: [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            pipes: $pipes,
            cwd: $workingDirectory,
        );

        $stdin = $pipes[0] ?? null;
        $stdoutPipe = $pipes[1] ?? null;
        $stderrPipe = $pipes[2] ?? null;
        if (!is_resource($process) || !is_resource($stdin) || !is_resource($stdoutPipe) || !is_resource($stderrPipe)) {
            throw new RuntimeException('Unable to start process: ' . implode(' ', $command));
        }

        fclose($stdin);
        $stdout = stream_get_contents($stdoutPipe);
        $stderr = stream_get_contents($stderrPipe);
        fclose($stdoutPipe);
        fclose($stderrPipe);
        $exitCode = proc_close($process);

        if ($stdout === false || $stderr === false) {
            throw new RuntimeException('Unable to read process output: ' . implode(' ', $command));
        }

        $result = new ProcessResult($command, $exitCode, $stdout, $stderr);
        if (!in_array($exitCode, $allowedExitCodes, strict: true)) {
            throw new RuntimeException($this->formatFailure($result));
        }

        return $result;
    }

    private function formatFailure(ProcessResult $result): string
    {
        return sprintf(
            "Command failed with exit code %d:\n%s\n\nstdout:\n%s\n\nstderr:\n%s",
            $result->exitCode,
            implode(' ', $result->command),
            $this->truncate($result->stdout),
            $this->truncate($result->stderr),
        );
    }

    private function truncate(string $output): string
    {
        $output = trim($output);
        if (strlen($output) <= 4000) {
            return $output;
        }

        return (
            substr($output, offset: 0, length: 2000) . "\n... output truncated ...\n" . substr($output, offset: -2000)
        );
    }
}
