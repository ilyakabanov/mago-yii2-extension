<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Parity;

use UnexpectedValueException;

/** Compares report outcomes only; the runner remains responsible for semantic type comparisons. */
final class RegressionChecker
{
    public function check(array $actual, array $saved): array
    {
        $known = $this->profiles($saved);
        $current = $this->profiles($actual);
        $errors = [];
        foreach ($known as $profile => $report) {
            $now = $current[$profile];
            if ($now['expected'] !== $report['expected']) {
                throw new UnexpectedValueException($profile . ': expected assertion count changed.');
            }
            foreach (['mismatches', 'analyzerErrors'] as $field) {
                $remaining = array_count_values($report[$field]);
                foreach ($now[$field] as $signature) {
                    if (($remaining[$signature] ?? 0) > 0) {
                        --$remaining[$signature];
                    } else {
                        $errors[] = $profile . ' ' . $field . ': new diagnostic ' . $signature;
                    }
                }
            }
        }
        return $errors;
    }

    private function profiles(array $payload): array
    {
        if (
            !is_bool($payload['success'] ?? null)
            || !is_array($payload['reports'] ?? null)
            || !array_is_list($payload['reports'])
        ) {
            throw new UnexpectedValueException('Missing or malformed report payload.');
        }
        $profiles = [];
        $allPassed = true;
        foreach ($payload['reports'] as $report) {
            $profile = is_array($report) ? $report['profile'] ?? null : null;
            if (!is_string($profile) || isset($profiles[$profile])) {
                throw new UnexpectedValueException('Missing, malformed or duplicate profile.');
            }
            foreach (['expected', 'comparisons', 'passed'] as $field) {
                if (!is_int($report[$field] ?? null) || $report[$field] < 0) {
                    throw new UnexpectedValueException($profile . ': invalid ' . $field . '.');
                }
            }
            if (
                ($report['infrastructureFailure'] ?? null) !== false
                || !in_array($report['exitCode'] ?? null, [0, 1], true)
            ) {
                throw new UnexpectedValueException($profile . ': worker/infrastructure failure.');
            }
            foreach (['mismatches', 'analyzerErrors'] as $field) {
                if (!is_array($report[$field] ?? null) || !array_is_list($report[$field])) {
                    throw new UnexpectedValueException($profile . ': invalid ' . $field . '.');
                }
                $signatures = [];
                foreach ($report[$field] as $issue) {
                    $signatures[] = $this->signature($issue, $field);
                }
                $report[$field] = $signatures;
            }
            if (
                $report['expected'] === 0
                || $report['comparisons'] !== $report['expected']
                || ($report['passed'] + count($report['mismatches'])) !== $report['expected']
            ) {
                throw new UnexpectedValueException($profile . ': inconsistent expected/comparisons/passed counts.');
            }
            $allPassed = $allPassed && $report['passed'] === $report['expected'];
            $profiles[$profile] = $report;
        }
        $names = array_keys($profiles);
        sort($names);
        if ($names !== ['awkward', 'console', 'custom', 'definition', 'params', 'web']) {
            throw new UnexpectedValueException('Reports must include all six original profiles exactly once.');
        }
        if ($payload['success'] !== $allPassed) {
            throw new UnexpectedValueException('Report success flag contradicts comparison counts.');
        }
        return $profiles;
    }

    private function signature(mixed $issue, string $field): string
    {
        if (
            !is_array($issue)
            || !is_string($issue['code'] ?? null)
            || $issue['code'] === ''
            || !is_string($issue['message'] ?? null)
            || $issue['message'] === ''
            || $issue['code'] === 'yii2/parity/pass'
            || str_starts_with($issue['code'], 'yii2/parity/') !== ($field === 'mismatches')
        ) {
            throw new UnexpectedValueException('Malformed ' . $field . ' diagnostic.');
        }
        $file = $issue['file'] ?? null;
        $line = $issue['line'] ?? null;
        if (array_key_exists('annotations', $issue)) {
            if (!is_array($issue['annotations']) || !array_is_list($issue['annotations'])) {
                throw new UnexpectedValueException('Malformed diagnostic annotations.');
            }
            $primary = array_values(array_filter(
                $issue['annotations'],
                static fn(mixed $annotation): bool => (
                    is_array($annotation)
                    && ($annotation['kind'] ?? null) === 'Primary'
                ),
            ));
            if (count($primary) !== 1) {
                throw new UnexpectedValueException('Diagnostic needs exactly one primary annotation.');
            }
            $file = $primary[0]['span']['file_id']['name'] ?? $primary[0]['span']['file_id']['path'] ?? null;
            $line = $primary[0]['span']['start']['line'] ?? null;
        }
        if (!is_string($file) || $file === '' || !is_int($line) || $line < 1) {
            throw new UnexpectedValueException('Malformed diagnostic primary filename/line.');
        }
        $file = str_replace('\\', '/', $file);
        // Worker paths are rooted at tests/; the saved report stores the relative filename.
        if (str_starts_with($file, '/') || preg_match('/^[A-Za-z]:\//', $file) === 1) {
            $position = strrpos($file, '/tests/');
            if ($position === false) {
                throw new UnexpectedValueException('Diagnostic path is not relative to tests/: ' . $file);
            }
            $file = substr($file, $position + strlen('/tests/'));
        }
        if (str_starts_with($file, 'tests/')) {
            $file = substr($file, strlen('tests/'));
        }
        if ($file === '' || preg_match('~(^|/)\.{1,2}(/|$)~', $file) === 1) {
            throw new UnexpectedValueException('Invalid diagnostic relative filename.');
        }
        return json_encode([
            $issue['code'],
            $issue['message'],
            $file,
            $line,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
