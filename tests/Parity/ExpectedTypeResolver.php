<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Parity;

use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\ArrayItem;
use Mago\Sdk\Analyzer\Type\KeyedArrayType;
use Mago\Sdk\Analyzer\Type\ListElement;
use Mago\Sdk\Analyzer\Type\ListType;
use Mago\Sdk\Analyzer\Type\NamedObjectType;

/** Decodes PHPStan's static(Class) marker without dropping late-static semantics. */
final class ExpectedTypeResolver
{
    public static function resolve(Type $type, bool $psrMessages = false): Type
    {
        $atoms = [];
        foreach ($type->atomicTypes as $atomic) {
            if ($atomic instanceof NamedObjectType) {
                if (strtolower($atomic->name) === 'parity\\logpayload') {
                    $payload = [Type::array(Type::fromAtomic(new \Mago\Sdk\Analyzer\Type\ScalarType(\Mago\Sdk\Analyzer\Type\ScalarTypeKind::ArrayKey)), Type::mixed()), Type::string(), Type::namedObject('Throwable')];
                    if ($psrMessages) { $payload[] = Type::namedObject('yii\\log\\PsrMessage'); }
                    foreach (Type::union(...$payload)->atomicTypes as $part) { $atoms[] = $part; }
                    continue;
                }
                if (strtolower($atomic->name) === 'parity\\statictype') {
                    $parameter = $atomic->parameters[0]->atomicTypes[0] ?? null;
                    if (!$parameter instanceof NamedObjectType) { throw new \RuntimeException('StaticType marker requires a named object.'); }
                    $atoms[] = new NamedObjectType($parameter->name, $parameter->parameters, $parameter->variances, true, false, $parameter->intersections, $parameter->remappedParameters);
                } else {
                    $intersections = $atomic->intersections === null ? null : array_map(static fn ($part) => self::resolve(Type::fromAtomic($part), $psrMessages)->atomicTypes[0], $atomic->intersections);
                    $atoms[] = new NamedObjectType($atomic->name, $atomic->parameters === null ? null : array_map(static fn ($part) => self::resolve($part, $psrMessages), $atomic->parameters), $atomic->variances, $atomic->static, $atomic->isThis, $intersections, $atomic->remappedParameters);
                }
            } elseif ($atomic instanceof KeyedArrayType) {
                $items = $atomic->knownItems === null ? null : array_map(static fn (ArrayItem $item): ArrayItem => new ArrayItem($item->key, $item->optional, self::resolve($item->type, $psrMessages)), $atomic->knownItems);
                $atoms[] = new KeyedArrayType($items, $atomic->keyType === null ? null : self::resolve($atomic->keyType, $psrMessages), $atomic->valueType === null ? null : self::resolve($atomic->valueType, $psrMessages), $atomic->nonEmpty);
            } elseif ($atomic instanceof ListType) {
                $elements = $atomic->knownElements === null ? null : array_map(static fn (ListElement $element): ListElement => new ListElement($element->index, $element->optional, self::resolve($element->type, $psrMessages)), $atomic->knownElements);
                $atoms[] = new ListType(self::resolve($atomic->elementType, $psrMessages), $elements, $atomic->knownCount, $atomic->nonEmpty);
            } else {
                $atoms[] = $atomic;
            }
        }
        return Type::fromAtomics(...$atoms)->withFlags($type->flags);
    }
}
