<?php

declare(strict_types=1);

namespace yii2\extensions\mago\Analyzer;

use Mago\Sdk\Analyzer\Argument;
use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\KeyedArrayType;
use Mago\Sdk\Analyzer\Type\ListType;
use Mago\Sdk\Analyzer\Type\MixedType;
use Mago\Sdk\Analyzer\Type\ScalarType;
use Mago\Sdk\Analyzer\Type\ScalarTypeKind;
use Mago\Sdk\Analyzer\Type\SimpleAtomicType;
use Mago\Sdk\Analyzer\Type\SimpleAtomicTypeKind;

/** Small semantic operations that do not require a host comparison request. */
final class TypeTools
{
    public static function mayBeNull(Type $type): bool
    {
        foreach ($type->atomicTypes as $atomic) {
            if (
                $atomic instanceof MixedType
                || $atomic instanceof SimpleAtomicType && $atomic->kind === SimpleAtomicTypeKind::Null
            ) {
                return true;
            }
        }

        return false;
    }

    /** @param list<Type> $types */
    public static function union(array $types): Type
    {
        $first = array_shift($types);
        if ($first === null) {
            return Type::never();
        }
        $second = array_shift($types);

        return $second === null ? $first : Type::union($first, $second, ...$types);
    }

    public static function arrayKey(): Type
    {
        return Type::fromAtomic(new ScalarType(ScalarTypeKind::ArrayKey));
    }

    public static function booleanArgument(?Argument $argument): ?bool
    {
        if ($argument === null || !$argument->unpacked) {
            return $argument?->type?->getLiteralBool();
        }
        $array = $argument->type?->atomicTypes[0] ?? null;
        if ($array instanceof ListType) {
            $elements = $array->knownElements ?? [];
            return count($elements) === 1 ? array_shift($elements)->type->getLiteralBool() : null;
        }
        if ($array instanceof KeyedArrayType) {
            $items = $array->knownItems ?? [];
            $item = count($items) === 1 ? array_shift($items) : null;
            return $item?->key->value === 0 ? $item->type->getLiteralBool() : null;
        }

        return null;
    }
}
