<?php

namespace Fixture;

use Vendor\Package\Http\Client\Factory;

final class FormatterCoveredRules implements FirstContract, SecondContract
{
    use FirstTrait;
    use SecondTrait;

    public static string $value;

    public function __construct(
        private readonly string $name
    ) {
    }

    final public static function create()
    {
        $factory = Factory::class;
        new Factory();

        return new $factory();
    }

    public function buildLabel(int $left, int $right, bool $enabled): string
    {
        ++$left;
        $left--;
        --$right;
        $right++;
        $total = $left + $right;
        $matches = $enabled && $left === $right;
        $selected = $matches ? $total : $right;

        return 'value:' . $selected;
    }

    public function formatArguments(string &$value, string &...$labels): array
    {
        $firstLabel = $labels[0] ?? '';
        $functionCall = sprintf('%s:%s', $value, $firstLabel);
        $methodCall = $this->combine($value, $firstLabel, $this->name);
        $closure = function (string &$candidate, int $limit = 1) use ($value, &$firstLabel): string {
            return $candidate . $value . $firstLabel . $limit;
        };
        $arrow = fn(string $candidate, int $limit = 1): string => $candidate . $limit;
        $multilineCall = $this->combine(
            'first argument with intentionally descriptive content',
            'second argument with intentionally descriptive content',
            'third argument with intentionally descriptive content',
            'fourth argument with intentionally descriptive content'
        );

        return [
            $functionCall,
            $methodCall,
            $closure($value),
            $arrow($firstLabel),
            $multilineCall
        ];
    }

    public function formatControlStructures(bool $first, bool $second, string $value): array
    {
        $result = [];
        if ($first) {
            $result[] = 'if';
        } elseif ($second) {
            $result[] = 'elseif';
        } else {
            $result[] = 'else';
        }
        while ($first) {
            $first = false;
            $result[] = 'while';
        }
        do {
            $second = false;
            $result[] = 'do';
        } while ($second);
        switch ($value) {
            case 'first':
                $result[] = 'switch';
                break;
            default:
                $result[] = 'default';
        }
        try {
            $result[] = 'try';
        } catch (\RuntimeException $exception) {
            $result[] = $exception->getMessage();
        } finally {
            $result[] = 'finally';
        }
        if ($first):
            $result[] = 'alternative';
        else:
            $result[] = 'alternative else';
        endif;
        if (
            $first
            && str_contains($value, 'first intentionally descriptive condition segment')
            && str_contains($value, 'second intentionally descriptive condition segment')
        ) {
            $result[] = 'multiline';
        }

        return $result;
    }

    private function combine(mixed ...$values): array
    {
        return $values;
    }
}
