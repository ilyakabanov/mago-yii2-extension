<?php

namespace Fixture;

use Vendor\Package\{Http\Client\Factory};

final  class FormatterCoveredRules implements FirstContract, SecondContract {
    use FirstTrait, SecondTrait;

    static public string $value;

    public function __construct( private  readonly  string  $name )
    {
    }

    public final static function create(  ) {
        $factory = Factory::class;
        new Factory;

        return new $factory;
    }

    public function buildLabel(int $left, int $right, bool $enabled): string
    {
        ++ $left;
        $left --;
        -- $right;
        $right ++;
        $total=$left+$right;
        $matches=$enabled&&$left===$right;
        $selected=$matches?$total:$right;

        return 'value:'.$selected;
    }

    public function formatArguments( string  &$value ,string  & ... $labels ): array
    {
        $firstLabel = $labels[0] ?? '';
        $functionCall = sprintf ( '%s:%s' ,$value,  $firstLabel );
        $methodCall = $this->combine( $value ,$firstLabel,  $this->name );
        $closure = function ( string  &$candidate ,int $limit=  1 ) use ( $value ,& $firstLabel ): string {
            return $candidate . $value . $firstLabel . $limit;
        };
        $arrow = fn( string  $candidate ,int $limit=  1 ): string => $candidate . $limit;
        $multilineCall = $this->combine('first argument with intentionally descriptive content', 'second argument with intentionally descriptive content',
    'third argument with intentionally descriptive content' , 'fourth argument with intentionally descriptive content');

        return [
            $functionCall,
            $methodCall,
            $closure($value),
            $arrow($firstLabel),
            $multilineCall
        ];
    }

    private function combine(mixed ...$values): array
    {
        return $values;
    }
}
