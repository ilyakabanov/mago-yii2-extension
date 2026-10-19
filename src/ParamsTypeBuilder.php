<?php

declare(strict_types=1);

namespace yii2\extensions\mago;

use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\ArrayItem;
use Mago\Sdk\Analyzer\Type\ArrayKey;
use Mago\Sdk\Analyzer\Type\ArrayKeyKind;
use Mago\Sdk\Analyzer\Type\KeyedArrayType;

/** Builds recursive params shapes with exact keys and widened scalar values. */
final class ParamsTypeBuilder
{
    /** @param array<array-key, mixed> $params */
    public static function build(array $params): Type
    {
        if ($params === []) {
            return Type::array(Type::mixed(), Type::mixed());
        }

        $items = [];
        foreach ($params as $key => $value) {
            $items[] = new ArrayItem(
                new ArrayKey(is_int($key) ? ArrayKeyKind::Integer : ArrayKeyKind::String, $key),
                false,
                self::buildValueType($value),
            );
        }
        return Type::fromAtomic(new KeyedArrayType($items, null, null, true));
    }

    private static function buildValueType(mixed $value): Type
    {
        return match (true) {
            $value === null => Type::null(),
            is_string($value) => Type::string(),
            is_int($value) => Type::int(),
            is_float($value) => Type::float(),
            is_bool($value) => Type::bool(),
            is_array($value) => self::build($value),
            default => Type::mixed(),
        };
    }
}
