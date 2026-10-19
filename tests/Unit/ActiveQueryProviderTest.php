<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use Mago\Sdk\Analyzer\InvocationKind;
use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\ClassLikeStringType;
use Mago\Sdk\Analyzer\Type\ClassLikeStringVariant;
use Mago\Sdk\Analyzer\Type\KeyedArrayType;
use Mago\Sdk\Analyzer\Type\NamedObjectType;
use Mago\Sdk\Analyzer\Type\ScalarType;
use Mago\Sdk\Analyzer\Type\ScalarTypeKind;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use yii2\extensions\mago\Analyzer\ActiveQueryProvider;
use yii2\extensions\mago\tests\Support\AnalyzerContext;

final class ActiveQueryProviderTest extends TestCase
{
    private AnalyzerContext $context;
    private ActiveQueryProvider $provider;

    protected function setUp(): void
    {
        $this->context = new AnalyzerContext();
        $this->provider = new ActiveQueryProvider();
        $this->context->addClass('yii\db\ActiveQuery');
        $this->context->addClass('yii\db\ActiveRecord');
        $this->context->addClass(
            'Invoice',
            ['yii\db\ActiveRecord'],
            ['$id', '$number', '$label', '$comments', '$secret'],
        );
        $this->context->addClass('Comment', ['yii\db\ActiveRecord']);
        $this->context->addMagicProperty('Invoice', '$id', Type::int());
        $this->context->addMagicProperty('Invoice', '$number', Type::string());
        $this->context->addMagicProperty('Invoice', '$label', Type::string(), readOnly: true);
        $this->context->addMagicProperty('Invoice', '$comments', Type::list(Type::namedObject('Comment')));
        $this->context->addMagicProperty('Invoice', '$secret', Type::string(), writeOnly: true);
    }

    public function testStaticFindBindsCalledModel(): void
    {
        $result = $this->resolve(Type::namedObject('Invoice'), 'find', static: true);
        self::assertSame('yii\db\ActiveQuery<Invoice>', (string) $result);
    }

    public function testFindOneAndFindAllBindModels(): void
    {
        self::assertSame(
            'Invoice|null',
            (string) $this->resolve(Type::namedObject('Invoice'), 'findOne', static: true),
        );
        self::assertSame(
            'array<array-key, Invoice>',
            (string) $this->resolve(Type::namedObject('Invoice'), 'findAll', static: true),
        );
    }

    public function testStaticFindFromConstrainedClassString(): void
    {
        $class = Type::fromAtomic(
            new ScalarType(
                ScalarTypeKind::ClassLikeString,
                new ClassLikeStringType(
                    ClassLikeStringVariant::OfType,
                    constraint: Type::namedObject('Invoice')->atomicTypes[0],
                ),
            ),
        );
        self::assertSame('yii\db\ActiveQuery<Invoice>', (string) $this->resolve($class, 'find', static: true));
    }

    public function testCustomFindKeepsQueryClassWithBoundIntersection(): void
    {
        $this->context->addClass('InvoiceQuery', ['yii\db\ActiveQuery']);
        $this->context->addMethod('Invoice', 'find', Type::namedObject('InvoiceQuery'), true);
        $result = $this->resolve(Type::namedObject('Invoice'), 'find', static: true);
        $query = $result->atomicTypes[0];
        self::assertInstanceOf(NamedObjectType::class, $query);
        self::assertSame('InvoiceQuery', $query->name);
        self::assertSame('Invoice', (string) $query->intersections[0]->parameters[0]);
    }

    public function testCustomFindReturningUnionKeepsNativeAnswer(): void
    {
        $this->context->addMethod(
            'Invoice',
            'find',
            Type::union(Type::namedObject('InvoiceQuery'), Type::namedObject('OtherQuery')),
            true,
        );
        self::assertNull($this->resolve(Type::namedObject('Invoice'), 'find', static: true));
    }

    public function testAllAndOneReadGenericRows(): void
    {
        $query = Type::namedObject('yii\db\ActiveQuery', Type::namedObject('Invoice'));
        self::assertSame('array<array-key, Invoice>', (string) $this->resolve($query, 'all'));
        self::assertSame('Invoice|null', (string) $this->resolve($query, 'one'));
    }

    public function testAsArrayBuildsRequiredOptionalAndRelatedFields(): void
    {
        $result = $this->resolve(Type::namedObject('yii\db\ActiveQuery', Type::namedObject('Invoice')), 'asArray');
        $shape = $result->atomicTypes[0]->parameters[0]->atomicTypes[0];
        self::assertInstanceOf(KeyedArrayType::class, $shape);
        self::assertSame(
            ['id', 'number', 'label', 'comments'],
            array_map(static fn($item) => $item->key->value, $shape->knownItems),
        );
        self::assertFalse($shape->knownItems[0]->optional);
        self::assertTrue($shape->knownItems[2]->optional);
        self::assertTrue($shape->knownItems[3]->optional);
        self::assertInstanceOf(KeyedArrayType::class, $shape->knownItems[3]->type->atomicTypes[0]);
    }

    public function testAsArrayFalseKeepsExactReceiver(): void
    {
        $query = Type::namedObject('yii\db\ActiveQuery', Type::namedObject('Invoice'));
        self::assertSame($query, $this->resolve($query, 'asArray', [AnalyzerContext::argument(
            Type::false(),
            'value',
        )]));
    }

    public function testAsArrayUnknownBooleanKeepsBothPossibleRows(): void
    {
        $query = Type::namedObject('yii\db\ActiveQuery', Type::namedObject('Invoice'));
        $result = $this->resolve($query, 'asArray', [AnalyzerContext::argument(Type::bool())]);
        self::assertCount(2, $result->atomicTypes[0]->parameters[0]->atomicTypes);
    }

    public function testAsArrayArrayRowsStayExact(): void
    {
        $row = Type::array(Type::string(), Type::int());
        $query = Type::namedObject('yii\db\ActiveQuery', $row);
        $result = $this->resolve($query, 'asArray');
        self::assertSame($row, $result->atomicTypes[0]->parameters[0]);
    }

    public function testReadOnlyOnlyShapeCanBeEmpty(): void
    {
        $this->context->addClass('Projection', ['yii\db\ActiveRecord'], ['$label']);
        $this->context->addMagicProperty('Projection', '$label', Type::string(), readOnly: true);
        $result = $this->resolve(Type::namedObject('yii\db\ActiveQuery', Type::namedObject('Projection')), 'asArray');
        $shape = $result->atomicTypes[0]->parameters[0]->atomicTypes[0];
        self::assertFalse($shape->nonEmpty);
        self::assertTrue($shape->knownItems[0]->optional);
    }

    public function testAsArrayUnpackedDefers(): void
    {
        self::assertNull($this->resolve(
            Type::namedObject('yii\db\ActiveQuery', Type::namedObject('Invoice')),
            'asArray',
            [AnalyzerContext::argument(Type::bool(), unpacked: true)],
        ));
    }

    public function testDerivedModelFromOneAndAll(): void
    {
        $this->context->addClass('InvoiceQuery', ['yii\db\ActiveQuery']);
        $this->context->addMethod(
            'InvoiceQuery',
            'one',
            Type::union(Type::namedObject('Invoice'), Type::array(Type::string(), Type::mixed()), Type::null()),
        );
        $this->context->addMethod(
            'InvoiceQuery',
            'all',
            Type::array(Type::int(), Type::union(
                Type::namedObject('Invoice'),
                Type::array(Type::string(), Type::mixed()),
            )),
        );
        self::assertSame('Invoice|null', (string) $this->resolve(Type::namedObject('InvoiceQuery'), 'one'));
        self::assertSame(
            'array<array-key, Invoice>',
            (string) $this->resolve(Type::namedObject('InvoiceQuery'), 'all'),
        );
    }

    public function testDerivedTightOneReturnIsPreserved(): void
    {
        $this->context->addClass('InvoiceQuery', ['yii\db\ActiveQuery']);
        $tight = Type::namedObject('Invoice');
        $this->context->addMethod('InvoiceQuery', 'one', $tight);
        $this->context->addMethod('InvoiceQuery', 'all', Type::array(Type::int(), $tight));
        self::assertSame($tight, $this->resolve(Type::namedObject('InvoiceQuery'), 'one'));
    }

    #[DataProvider('unsafeDerivedReturns')]
    public function testUnsafeDerivedModelKeepsNativeAnswer(Type $one, Type $all): void
    {
        $this->context->addClass('InvoiceQuery', ['yii\db\ActiveQuery']);
        $this->context->addMethod('InvoiceQuery', 'one', $one);
        $this->context->addMethod('InvoiceQuery', 'all', $all);
        self::assertNull($this->resolve(Type::namedObject('InvoiceQuery'), 'one'));
    }

    public static function unsafeDerivedReturns(): iterable
    {
        $model = Type::namedObject('Invoice');
        yield 'false one' => [Type::union($model, Type::false()), Type::array(Type::int(), $model)];
        yield 'null all' => [$model, Type::union(Type::array(Type::int(), $model), Type::null())];
        yield 'null element' => [$model, Type::array(Type::int(), Type::union($model, Type::null()))];
        yield 'object all' => [$model, Type::object()];
        yield 'unknown all element' => [$model, Type::array(Type::int(), Type::mixed())];
        yield 'two models' => [Type::union($model, Type::namedObject('Comment')), Type::array(Type::int(), $model)];
    }

    public function testBatchAndEachCarryRowTypes(): void
    {
        $query = Type::namedObject('yii\db\ActiveQuery', Type::namedObject('Invoice'));
        self::assertSame(
            'yii\db\BatchQueryResult<int, array<array-key, Invoice>>',
            (string) $this->resolve($query, 'batch'),
        );
        self::assertSame('yii\db\BatchQueryResult<array-key, Invoice>', (string) $this->resolve($query, 'each'));
    }

    public function testFluentMethodsKeepBoundQuery(): void
    {
        $query = Type::namedObject('yii\db\ActiveQuery', Type::namedObject('Invoice'));
        foreach (['where', 'andWhere', 'indexBy', 'with', 'joinWith', 'select', 'limit', 'orderBy'] as $method) {
            self::assertSame($query, $this->resolve($query, $method));
        }
    }

    public function testHasOneAndManyInferRelatedModel(): void
    {
        $args = [
            AnalyzerContext::argument(Type::literalString('Comment')),
            AnalyzerContext::argument(Type::array(Type::string(), Type::string())),
        ];
        self::assertSame(
            'yii\db\ActiveQuery<Comment>',
            (string) $this->resolve(Type::namedObject('Invoice'), 'hasOne', $args),
        );
        self::assertSame(
            'yii\db\ActiveQuery<Comment>',
            (string) $this->resolve(Type::namedObject('Invoice'), 'hasMany', $args),
        );
    }

    public function testUnionReceiverDoesNotInventSingleModel(): void
    {
        self::assertNull($this->resolve(
            Type::union(Type::namedObject('InvoiceQuery'), Type::namedObject('OtherQuery')),
            'one',
        ));
    }

    public function testRowIndependentMethodsKeepNativeReturnTypes(): void
    {
        $query = Type::namedObject('yii\db\ActiveQuery', Type::namedObject('Invoice'));
        foreach (['column', 'scalar', 'count', 'exists', 'createCommand', 'populate'] as $method) {
            self::assertNull($this->resolve($query, $method));
        }
    }

    private function resolve(Type $receiver, string $method, array $arguments = [], bool $static = false): ?Type
    {
        return $this->provider->getReturnType($this->context->context(
            $receiver,
            $method,
            $arguments,
            $static ? InvocationKind::StaticMethod : InvocationKind::InstanceMethod,
        ));
    }
}
