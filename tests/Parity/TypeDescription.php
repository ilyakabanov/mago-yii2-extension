<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Parity;

use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\KeyedArrayType;
use Mago\Sdk\Analyzer\Type\ListType;
use Mago\Sdk\Analyzer\Type\NamedObjectType;

final class TypeDescription
{
    public static function describe(Type $type): string
    {
        if (($literal = $type->getLiteralString()) !== null) { return var_export($literal, true); }
        if (($literal = $type->getLiteralInt()) !== null) { return (string) $literal; }
        if (($literal = $type->getLiteralBool()) !== null) { return $literal ? 'true' : 'false'; }
        $parts = [];
        foreach ($type->atomicTypes as $atomic) {
            if ($atomic instanceof NamedObjectType) {
                $name = $atomic->isThis ? '$this(' . $atomic->name . ')' : ($atomic->static ? 'static(' . $atomic->name . ')' : $atomic->name);
                $parameters = array_map(self::describe(...), $atomic->parameters ?? []);
                $intersection = implode('&', array_map(static fn ($part): string => self::describe(Type::fromAtomic($part)), $atomic->intersections ?? []));
                $parts[] = $name . ($parameters === [] ? '' : '<' . implode(', ', $parameters) . '>') . ($intersection === '' ? '' : '&' . $intersection);
            } elseif ($atomic instanceof KeyedArrayType) {
                if ($atomic->knownItems !== null) {
                    $items = [];
                    foreach ($atomic->knownItems as $item) { $items[] = var_export($item->key->value, true) . ($item->optional ? '?' : '') . ': ' . self::describe($item->type); }
                    $parts[] = 'array{' . implode(', ', $items) . '}';
                } else {
                    $parts[] = ($atomic->nonEmpty ? 'non-empty-array' : 'array') . '<' . ($atomic->keyType !== null ? self::describe($atomic->keyType) : 'mixed') . ', ' . ($atomic->valueType !== null ? self::describe($atomic->valueType) : 'mixed') . '>';
                }
            } elseif ($atomic instanceof ListType) {
                $parts[] = ($atomic->nonEmpty ? 'non-empty-list' : 'list') . '<' . self::describe($atomic->elementType) . '>';
            } else {
                $parts[] = (string) $atomic;
            }
        }
        return implode('|', $parts);
    }
}
