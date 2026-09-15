<?php

namespace Fixture;

use Vendor\Package\{Http\Client\Factory};

final  class FormatterCoveredRules implements FirstContract, SecondContract {
    use FirstTrait, SecondTrait;

    static public string $value;

    public final static function create() {
        $factory = Factory::class;
        new Factory;

        return new $factory;
    }
}
