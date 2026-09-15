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

interface FormatterContract
{
    public function format(string $value): string;
}

final class FunctionDeclarationLayout
{
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
}
