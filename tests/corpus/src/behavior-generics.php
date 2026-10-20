<?php

declare(strict_types=1);

namespace Corpus\BehaviorGenerics;

use yii\base\Behavior;
use yii\base\Component;

use function Mago\confirm;

class Owner extends Component {}

class GenericBehavior extends Behavior
{
    /**
     * @template TValue
     * @param TValue $value
     * @return TValue
     */
    public function identity(mixed $value): mixed
    {
        return $value;
    }

    /**
     * @template TValue of string
     * @param TValue $value
     * @return TValue
     */
    public function bounded(mixed $value): mixed
    {
        return $value;
    }

    /**
     * @template TValue of string
     * @param TValue $first
     * @param TValue $second
     * @return TValue
     */
    public function repeated(mixed $first, mixed $second): mixed
    {
        return $first;
    }

    /**
     * @template TValue
     * @param TValue $value
     * @param-out TValue $out
     * @return TValue
     */
    public function copy(mixed $value, mixed &$out): mixed
    {
        $out = $value;
        return $value;
    }

    /**
     * @template TValue
     * @param TValue $value
     * @return list<list<TValue>>
     */
    public function nested(mixed $value, int $count = 1): array
    {
        return [[$value]];
    }
}

class InheritedBehavior extends GenericBehavior {}

function takesString(string $value): void {}

function takesInt(int $value): void {}

function compare(Owner $owner, GenericBehavior $direct, string $value, int $number): void
{
    confirm($direct->identity($value), 'string');
    confirm($owner->identity($value), 'string');
    confirm($direct->identity('literal'), "string('literal')");
    confirm($owner->identity('literal'), "string('literal')");
    confirm($direct->identity(42), 'int(42)');
    confirm($owner->identity(42), 'int(42)');
    confirm($direct->identity(true), 'true');
    confirm($owner->identity(true), 'true');
    confirm($direct->identity($number), 'int');
    confirm($owner->identity($number), 'int');
    confirm($owner->identity(value: $value), 'string');
    confirm($direct->bounded($value), 'string');
    confirm($owner->bounded($value), 'string');
    confirm($direct->bounded('literal'), "string('literal')");
    confirm($owner->bounded('literal'), "string('literal')");
    confirm($direct->repeated($value, $value), 'string');
    confirm($owner->repeated(second: $value, first: $value), 'string');
    confirm($direct->nested($value), 'list<list<string>>');
    confirm($owner->nested(count: 2, value: $value), 'list<list<string>>');
    $nativeOut = null;
    $forwardedOut = null;
    confirm($direct->copy($value, $nativeOut), 'string');
    confirm($owner->copy($value, $forwardedOut), 'string');
    confirm($nativeOut, 'string');
    takesString($owner->identity($value));
    // @mago-expect analysis:invalid-argument
    takesInt($owner->identity($value));
    // @mago-expect analysis:invalid-argument
    $owner->bounded($number);
    // @mago-expect analysis:invalid-argument
    $owner->repeated($value, $number);
    // @mago-expect analysis:invalid-argument
    $owner->nested($value, 'wrong');
    // @mago-expect analysis:too-few-arguments
    $owner->identity();
    // @mago-expect analysis:invalid-named-argument
    $owner->identity(unknown: $value);
}
