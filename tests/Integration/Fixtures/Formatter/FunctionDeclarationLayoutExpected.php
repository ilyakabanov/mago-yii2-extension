<?php

namespace Fixture;

function formatValue(string $value): string
{
    return $value;
}

function concatenateValues(
    string $firstValue,
    string $secondValue,
    string $thirdValue,
    string $fourthValue,
    string $fifthValue
): string {
    return $firstValue . $secondValue . $thirdValue . $fourthValue . $fifthValue;
}

function normalizeNullableValue(?string $value): ?string
{
    return $value;
}

interface FormatterContract
{
    public function format(string $value): string;
}

final class FunctionDeclarationLayout
{
    private ?string $fallback = null;

    public function normalize(string $value): string
    {
        return $value;
    }

    public function concatenate(
        string $firstValue,
        string $secondValue,
        string $thirdValue,
        string $fourthValue,
        string $fifthValue
    ): string {
        return $firstValue . $secondValue . $thirdValue . $fourthValue . $fifthValue;
    }

    public function createClosure(): \Closure
    {
        return function (
            string $firstValue,
            string $secondValue,
            string $thirdValue,
            string $fourthValue,
            string $fifthValue
        ): string {
            return $firstValue . $secondValue . $thirdValue . $fourthValue . $fifthValue;
        };
    }

    public function normalizeNullable(?string $value): ?string
    {
        $fallback = $this->fallback;
        $normalizeClosure = function (?string $candidate) use ($fallback): ?string {
            return $candidate ?? $fallback;
        };
        $normalizeArrow = fn(?string $candidate): ?string => $candidate ?? $fallback;

        return $normalizeArrow($normalizeClosure($value));
    }
}
