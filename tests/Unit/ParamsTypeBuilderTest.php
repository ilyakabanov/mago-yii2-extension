<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\ArrayKeyKind;
use Mago\Sdk\Analyzer\Type\KeyedArrayType;
use PHPUnit\Framework\TestCase;
use stdClass;
use yii2\extensions\mago\ParamsTypeBuilder;

final class ParamsTypeBuilderTest extends TestCase
{
    public function testEmptyParamsUseGenericMixedArray(): void
    {
        self::assertSame('array<mixed, mixed>', (string) ParamsTypeBuilder::build([]));
    }

    public function testRecursiveShapeWidensValuesAndKeepsAwkwardKeys(): void
    {
        $keys = ['', 'with space', "quote'key", 'dot.key', 'slash\\key', '01', -3, 2];
        $params = array_combine($keys, ['a', 42, 1.5, true, null, new stdClass(), ['a' => false], []]);
        $type = ParamsTypeBuilder::build($params);
        self::assertInstanceOf(Type::class, $type);
        $shape = $type->atomicTypes[0];
        self::assertInstanceOf(KeyedArrayType::class, $shape);
        self::assertTrue($shape->nonEmpty);
        self::assertNull($shape->keyType);
        self::assertNull($shape->valueType);
        self::assertSame($keys, array_map(static fn($item) => $item->key->value, $shape->knownItems));
        foreach ($shape->knownItems as $index => $item) {
            self::assertFalse($item->optional);
            self::assertSame(is_int($keys[$index]) ? ArrayKeyKind::Integer : ArrayKeyKind::String, $item->key->kind);
        }
        self::assertSame(
            ['string', 'int', 'float', 'bool', 'null', 'mixed'],
            array_map(static fn($item) => (string) $item->type, array_slice($shape->knownItems, 0, 6)),
        );
        self::assertSame('bool', (string) $shape->knownItems[6]->type->atomicTypes[0]->knownItems[0]->type);
        self::assertSame('array<mixed, mixed>', (string) $shape->knownItems[7]->type);
        self::assertNotSame('', $type->encode());
    }
}
