<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\ArrayKeyKind;
use Mago\Sdk\Analyzer\Type\KeyedArrayType;
use PHPUnit\Framework\TestCase;
use stdClass;
use yii2\extensions\mago\ParamsTypeBuilder;
use yii2\extensions\phpstan\tests\provider\ParamsTypeBuilderProvider as OriginalParamsCases;

require_once __DIR__ . '/../reference/provider/ParamsTypeBuilderProvider.php';

final class ParamsTypeBuilderTest extends TestCase
{
    public function testEmptyParamsUseGenericMixedArray(): void
    {
        self::assertSame('array<mixed, mixed>', (string) ParamsTypeBuilder::build([]));
    }

    public function testOriginalPortableShapeCases(): void
    {
        $expected = [
            'empty params' => 'array<mixed, mixed>',
            'keys needing quoting' => [
                'turnstile.siteKey' => 'string',
                "O'Reilly" => 'string',
                'C:\\path' => 'string',
                'with space' => 'int',
            ],
            'list becomes positional shape' => ['tags' => ['string', 'string']],
            'mixed int and string keys' => [0 => 'string', 1 => 'string', 10 => 'string', 'adminEmail' => 'string'],
            'nested shapes and nested empty arrays' => [
                'mail' => ['from' => 'string', 'cc' => 'array<mixed, mixed>'],
                'empty' => 'array<mixed, mixed>',
            ],
            'non-scalar values become mixed' => ['object' => 'mixed', 'closure' => 'mixed', 'resource' => 'mixed'],
            'numeric-string keys' => ['01' => 'string', '1.5' => 'string', 7 => 'string'],
            'scalars are generalized' => [
                'name' => 'string', 'ttl' => 'int', 'ratio' => 'float', 'debug' => 'bool', 'none' => 'null',
            ],
            'sparse int keys' => ['sparse' => [2 => 'string', 5 => 'string']],
        ];
        $visited = [];
        foreach (OriginalParamsCases::paramsProvider() as $name => [$params]) {
            try {
                if ($name === 'non-scalar values become mixed') {
                    self::assertIsResource($params['resource']);
                }
                self::assertArrayHasKey($name, $expected);
                $this->assertShape($expected[$name], ParamsTypeBuilder::build($params));
                $visited[] = $name;
            } finally {
                if (isset($params['resource']) && is_resource($params['resource'])) {
                    fclose($params['resource']);
                }
            }
        }
        self::assertSame(array_keys($expected), $visited);
    }

    public function testShapeRetainsAllThreeHundredKeys(): void
    {
        $params = array_fill_keys(range(1, 300), 'value');
        $this->assertShape(array_fill_keys(range(1, 300), 'string'), ParamsTypeBuilder::build($params));
        self::assertNotSame('', ParamsTypeBuilder::build($params)->encode());
    }

    private function assertShape(array|string $expected, Type $type): void
    {
        if (is_string($expected)) {
            self::assertSame($expected, (string) $type);
            return;
        }
        self::assertCount(1, $type->atomicTypes);
        $shape = $type->atomicTypes[0];
        self::assertInstanceOf(KeyedArrayType::class, $shape);
        self::assertTrue($shape->nonEmpty);
        self::assertNull($shape->keyType);
        self::assertNull($shape->valueType);
        self::assertNotNull($shape->knownItems);
        self::assertSame(array_keys($expected), array_map(static fn($item) => $item->key->value, $shape->knownItems));
        foreach ($shape->knownItems as $item) {
            self::assertFalse($item->optional);
            self::assertSame(is_int($item->key->value) ? ArrayKeyKind::Integer : ArrayKeyKind::String, $item->key->kind);
            $this->assertShape($expected[$item->key->value], $item->type);
        }
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
