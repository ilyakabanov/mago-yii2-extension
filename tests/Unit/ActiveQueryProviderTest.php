<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use Mago\Sdk\Analyzer\InvocationKind;
use Mago\Sdk\Analyzer\Metadata\ClassLikeMetadata;
use Mago\Sdk\Analyzer\Metadata\TemplateMetadata;
use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\CallableSignature;
use Mago\Sdk\Analyzer\Type\CallableType;
use Mago\Sdk\Analyzer\Type\ClassLikeStringType;
use Mago\Sdk\Analyzer\Type\ClassLikeStringVariant;
use Mago\Sdk\Analyzer\Type\GenericParameterType;
use Mago\Sdk\Analyzer\Type\GenericParent;
use Mago\Sdk\Analyzer\Type\GenericParentKind;
use Mago\Sdk\Analyzer\Type\KeyedArrayType;
use Mago\Sdk\Analyzer\Type\NamedObjectType;
use Mago\Sdk\Analyzer\Type\ScalarType;
use Mago\Sdk\Analyzer\Type\ScalarTypeKind;
use Mago\Sdk\Analyzer\Type\Variance;
use Mago\Sdk\Analyzer\TypeComparison;
use Mago\Sdk\Internal\Analyzer\Protocol;
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
        $this->context->addMethod('Invoice', 'findOne', null);
        $this->context->addMethod('Invoice', 'findAll', null);
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

    public function testStaticFindKeepsSubclassAndLateStaticRows(): void
    {
        $this->context->addClass('SpecialInvoice', ['Invoice', 'yii\db\ActiveRecord']);
        $result = $this->resolve(Type::namedObject('SpecialInvoice'), 'find', static: true);
        self::assertSame('yii\db\ActiveQuery<SpecialInvoice>', (string) $result);
        foreach ([false, true] as $isThis) {
            $receiver = Type::fromAtomic(new NamedObjectType('Invoice', null, null, true, $isThis, null, false));
            $result = $this->resolve($receiver, 'find', static: true);
            self::assertTrue($result->atomicTypes[0]->parameters[0]->atomicTypes[0]->static);
        }
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

    public function testSingleRowTemplateKeepsCustomQueryAndArrayShape(): void
    {
        $constraint = Type::union(Type::namedObject('Invoice'), Type::array(Type::string(), Type::mixed()));
        $this->addGenericQuery('RowQuery', ['TRow' => $constraint], true);
        $shape = $this->resolve(
            Type::namedObject('yii\db\ActiveQuery', Type::namedObject('Invoice')),
            'asArray',
        )->atomicTypes[0]->parameters[0];
        $this->cacheContainment($shape, $constraint, true);
        $this->cacheContainment(Type::array(Type::string(), Type::int()), $constraint, true);
        $query = Type::namedObject('RowQuery', Type::namedObject('Invoice'));
        self::assertSame('Invoice|null', (string) $this->resolve($query, 'one'));
        $arrays = $this->resolve($query, 'asArray');
        self::assertSame('RowQuery', $arrays->atomicTypes[0]->name);
        self::assertInstanceOf(KeyedArrayType::class, $arrays->atomicTypes[0]->parameters[0]->atomicTypes[0]);
        self::assertSame($query, $this->resolve($query, 'asArray', [AnalyzerContext::argument(Type::false())]));
        $arrayRow = Type::array(Type::string(), Type::int());
        $result = $this->resolve(Type::namedObject('RowQuery', $arrayRow), 'asArray');
        self::assertSame($arrayRow, $result->atomicTypes[0]->parameters[0]);
    }

    public function testFindBindsSupportedSingleRowTemplateWithoutLosingLateStatic(): void
    {
        $this->addGenericQuery('RowQuery', ['TRow' => Type::namedObject('Invoice')], true);
        $lateInvoice = Type::fromAtomic(new NamedObjectType('Invoice', null, null, true, false, null, false));
        $query = Type::namedObject('RowQuery', $lateInvoice);
        $this->context->addMethod('Invoice', 'find', $query, true);
        $plain = $this->resolve(Type::namedObject('Invoice'), 'find', static: true);
        self::assertSame('RowQuery', $plain->atomicTypes[0]->name);
        self::assertSame('Invoice', $plain->atomicTypes[0]->parameters[0]->atomicTypes[0]->name);
        self::assertFalse($plain->atomicTypes[0]->parameters[0]->atomicTypes[0]->static);
        self::assertSame($query, $this->resolve($lateInvoice, 'find', static: true));
    }

    #[DataProvider('rowTemplatePositions')]
    public function testMultipleTemplatesRewriteOnlyTheProvenRow(int $position): void
    {
        $constraint = Type::union(Type::namedObject('yii\db\ActiveRecord'), Type::array(Type::string(), Type::mixed()));
        $constraints = ['TFirst' => $constraint, 'TSecond' => $constraint, 'TThird' => $constraint];
        $this->addGenericQuery('MultiQuery', $constraints, $position);
        $model = Type::namedObject('Invoice');
        $shape = $this->resolve(
            Type::namedObject('yii\db\ActiveQuery', $model),
            'asArray',
        )->atomicTypes[0]->parameters[0];
        $this->cacheContainment($shape, $constraint, true);
        $this->cacheContainment($model, $constraint, true);
        $parameters = [$model, $model, $model];
        $variances = [Variance::Invariant, Variance::Invariant, Variance::Invariant];
        $query = Type::fromAtomic(
            new NamedObjectType(
                'MultiQuery',
                $parameters,
                $variances,
                true,
                true,
                [
                    Type::namedObject('Marker')->atomicTypes[0],
                    Type::namedObject('yii\db\ActiveQuery', $model)->atomicTypes[0],
                ],
                true,
            ),
        );
        $result = $this->resolve($query, 'asArray')->atomicTypes[0];
        $parameters[$position] = $shape;
        self::assertSame('MultiQuery', $result->name);
        self::assertSame(
            array_map(static fn(Type $type) => $type->encode(), $parameters),
            array_map(static fn(Type $type) => $type->encode(), $result->parameters),
        );
        foreach ($parameters as $index => $parameter) {
            if ($index !== $position) {
                self::assertSame($parameter, $result->parameters[$index]);
            }
        }
        self::assertSame($variances, $result->variances);
        self::assertTrue($result->static);
        self::assertTrue($result->isThis);
        self::assertTrue($result->remappedParameters);
        self::assertSame($query->atomicTypes[0]->intersections[0], $result->intersections[0]);
        self::assertSame($shape->encode(), $result->intersections[1]->parameters[0]->encode());
        self::assertNull($this->resolve(Type::namedObject('MultiQuery', $model, $model), 'asArray'));
    }

    public static function rowTemplatePositions(): iterable
    {
        yield 'first' => [0];
        yield 'second' => [1];
        yield 'third' => [2];
    }

    public function testMultipleTemplateFindRebindsTheRowForCalledSubclasses(): void
    {
        $constraint = Type::union(Type::namedObject('yii\db\ActiveRecord'), Type::array(Type::string(), Type::mixed()));
        $this->addGenericQuery(
            'MultiQuery',
            ['TKey' => Type::string(), 'TRow' => $constraint, 'TTag' => Type::int()],
            1,
        );
        $this->context->addClass('SpecialInvoice', ['Invoice', 'yii\db\ActiveRecord']);
        $this->cacheContainment(Type::string(), Type::string(), true);
        $this->cacheContainment(Type::int(), Type::int(), true);
        $query = Type::namedObject(
            'MultiQuery',
            Type::string(),
            Type::fromAtomic(new NamedObjectType('Invoice', [], null, true, false, null, false)),
            Type::int(),
        );
        $this->context->addMethod('Invoice', 'find', $query, true);
        $this->context->addMethod('SpecialInvoice', 'find', $query, true);
        foreach (['Invoice', 'SpecialInvoice'] as $name) {
            $model = Type::namedObject($name);
            $this->cacheContainment(Type::fromAtomic($model->atomicTypes[0]), $constraint, true);
            $result = $this->resolve($model, 'find', static: true)->atomicTypes[0];
            self::assertSame('MultiQuery', $result->name);
            self::assertSame($query->atomicTypes[0]->parameters[0], $result->parameters[0]);
            self::assertSame($query->atomicTypes[0]->parameters[2], $result->parameters[2]);
            self::assertSame($name, $result->parameters[1]->atomicTypes[0]->name);
            self::assertFalse($result->parameters[1]->atomicTypes[0]->static);
        }
    }

    public function testMultipleTemplateInheritedFindKeepsExplicitConcreteRow(): void
    {
        $constraint = Type::union(Type::namedObject('yii\db\ActiveRecord'), Type::array(Type::string(), Type::mixed()));
        $this->addGenericQuery('MultiQuery', ['TKey' => Type::string(), 'TRow' => $constraint], 1);
        $this->context->addClass('SpecialInvoice', ['Invoice', 'yii\db\ActiveRecord']);
        $query = Type::namedObject('MultiQuery', Type::string(), Type::namedObject('Invoice'));
        $this->context->addMethod('SpecialInvoice', 'find', $query, true);
        $this->cacheContainment(
            Type::fromAtomic(Type::namedObject('SpecialInvoice')->atomicTypes[0]),
            $constraint,
            true,
        );
        self::assertSame($query, $this->resolve(Type::namedObject('SpecialInvoice'), 'find', static: true));
    }

    #[DataProvider('dependentQueryBounds')]
    public function testDependentNonRowBoundFallsBackInsteadOfComparingUninstantiatedTemplate(Type $dependent): void
    {
        $constraint = Type::union(Type::namedObject('yii\db\ActiveRecord'), Type::array(Type::string(), Type::mixed()));
        $this->addGenericQuery('MultiQuery', ['TRow' => $constraint, 'TList' => $dependent], 0);
        $model = Type::namedObject('Invoice');
        $list = Type::list($model);
        $query = Type::namedObject('MultiQuery', $model, $list);
        $shape = $this->resolve(
            Type::namedObject('yii\db\ActiveQuery', $model),
            'asArray',
        )->atomicTypes[0]->parameters[0];
        $this->cacheContainment($shape, $constraint, true);
        // Raw template containment is not evidence for list<the replacement array row>.
        $this->cacheContainment($list, $dependent, true);
        $result = $this->resolve($query, 'asArray')->atomicTypes[0];
        self::assertSame('yii\db\ActiveQuery', $result->name);
        self::assertSame($shape->encode(), $result->parameters[0]->encode());
    }

    public static function dependentQueryBounds(): iterable
    {
        $symbol = Type::fromAtomic(
            new GenericParameterType(
                'TRow',
                Type::union(Type::namedObject('yii\db\ActiveRecord'), Type::array(Type::string(), Type::mixed())),
                new GenericParent(GenericParentKind::ClassLike, 'multiquery'),
                null,
            ),
        );
        yield 'direct template' => [$symbol];
        yield 'nested list' => [Type::list($symbol)];
        yield 'nested array' => [Type::array(Type::string(), Type::list($symbol))];
        yield 'nested named object' => [Type::namedObject('Box', $symbol)];
        yield 'unknown callable structure' => [Type::fromAtomic(
            new CallableType(new CallableSignature(false, false, [], $symbol, null, []), null),
        )];
    }

    #[DataProvider('closedQueryBounds')]
    public function testClosedNonRowBoundsAreValidatedWithoutChangingTheirArguments(
        Type $bound,
        Type $argument,
        bool $valid,
    ): void {
        $constraint = Type::union(Type::namedObject('yii\db\ActiveRecord'), Type::array(Type::string(), Type::mixed()));
        $this->addGenericQuery('MultiQuery', ['TRow' => $constraint, 'TOther' => $bound], 0);
        $model = Type::namedObject('Invoice');
        $shape = $this->resolve(
            Type::namedObject('yii\db\ActiveQuery', $model),
            'asArray',
        )->atomicTypes[0]->parameters[0];
        $this->cacheContainment($shape, $constraint, true);
        $this->cacheContainment($argument, $bound, $valid);
        $result = $this->resolve(Type::namedObject('MultiQuery', $model, $argument), 'asArray')->atomicTypes[0];
        self::assertSame($valid ? 'MultiQuery' : 'yii\db\ActiveQuery', $result->name);
        self::assertSame($shape->encode(), $result->parameters[0]->encode());
        if ($valid) {
            self::assertSame($argument, $result->parameters[1]);
        }
    }

    public static function closedQueryBounds(): iterable
    {
        $list = Type::list(Type::namedObject('Invoice'));
        $array = Type::array(Type::string(), Type::list(Type::int()));
        $object = Type::namedObject('Box', Type::string());
        yield 'list' => [$list, $list, true];
        yield 'array' => [$array, $array, true];
        yield 'named object' => [$object, $object, true];
        yield 'literal argument retained' => [Type::string(), Type::literalString('key'), true];
        yield 'failed native containment' => [Type::string(), Type::int(), false];
    }

    public function testRecordOnlyRowConstraintFallsBackToBaseQueryForArrays(): void
    {
        $record = Type::namedObject('yii\db\ActiveRecord');
        $this->addGenericQuery('MultiQuery', ['TKey' => Type::string(), 'TRow' => $record], 1);
        $model = Type::namedObject('Invoice');
        $query = Type::namedObject('MultiQuery', Type::string(), $model);
        $shape = $this->resolve(
            Type::namedObject('yii\db\ActiveQuery', $model),
            'asArray',
        )->atomicTypes[0]->parameters[0];
        $this->cacheContainment(Type::string(), Type::string(), true);
        $this->cacheContainment($shape, $record, false);
        $result = $this->resolve($query, 'asArray')->atomicTypes[0];
        self::assertSame('yii\db\ActiveQuery', $result->name);
        self::assertCount(1, $result->parameters);
        self::assertSame($shape->encode(), $result->parameters[0]->encode());
        self::assertSame($query, $this->resolve($query, 'asArray', [AnalyzerContext::argument(Type::false())]));
    }

    public function testAmbiguousSymbolicProjectionKeepsNativeInference(): void
    {
        $record = Type::namedObject('yii\db\ActiveRecord');
        $this->addGenericQuery('AmbiguousQuery', ['TFirst' => $record, 'TSecond' => $record], 0);
        $parent = new GenericParent(GenericParentKind::ClassLike, 'ambiguousquery');
        $symbols = array_map(static fn(string $name) => Type::fromAtomic(
            new GenericParameterType($name, $record, $parent, null),
        ), ['TFirst', 'TSecond']);
        $this->cacheContainment(
            Type::namedObject('AmbiguousQuery', ...$symbols),
            Type::namedObject('yii\db\ActiveQuery', $symbols[1]),
            true,
        );
        $query = Type::namedObject('AmbiguousQuery', Type::namedObject('Invoice'), Type::namedObject('Invoice'));
        foreach (['one', 'all', 'asArray'] as $method) {
            self::assertNull($this->resolve($query, $method));
        }
    }

    public function testSingleUnusedTemplateDoesNotBecomeRowEvenWhenConcreteTypeMatches(): void
    {
        $this->addGenericQuery('UnusedQuery', ['TUnused' => Type::namedObject('Invoice')], false);
        $query = Type::namedObject('UnusedQuery', Type::namedObject('Invoice'));
        foreach (['one', 'all', 'asArray'] as $method) {
            self::assertNull($this->resolve($query, $method), $method);
        }
        $this->addGenericQuery('KeyOnlyQuery', ['TKey' => Type::string()], false);
        $query = Type::namedObject('KeyOnlyQuery', Type::string());
        self::assertNull($this->resolve($query, 'one'));
        self::assertNull($this->resolve($query, 'asArray'));
    }

    public function testCustomQueryParametersAreNotAssumedToBeRows(): void
    {
        $this->addGenericQuery('KeyedQuery', ['TKey' => Type::string(), 'TModel' => Type::namedObject('Invoice')]);
        $query = Type::namedObject('KeyedQuery', Type::string(), Type::namedObject('Invoice'));
        foreach (['one', 'all', 'batch', 'each', 'asArray'] as $method) {
            self::assertNull($this->resolve($query, $method), $method);
            self::assertNull($this->resolve(Type::namedObject('KeyedQuery', Type::string()), $method), $method);
        }
        self::assertSame($query, $this->resolve($query, 'where'));
        $this->context->addMethod('Invoice', 'find', $query, true);
        self::assertNull($this->resolve(Type::namedObject('Invoice'), 'find', static: true));
    }

    public function testCustomQueryBindingDoesNotRewriteTemplatePositions(): void
    {
        $this->addGenericQuery('KeyedQuery', ['TKey' => Type::string(), 'TModel' => Type::namedObject('Invoice')]);
        $lateInvoice = Type::fromAtomic(new NamedObjectType('Invoice', null, null, true, false, null, false));
        $query = Type::fromAtomic(
            new NamedObjectType(
                'KeyedQuery',
                [Type::string(), Type::namedObject('Invoice')],
                null,
                false,
                false,
                [Type::namedObject('yii\db\ActiveQuery', $lateInvoice)->atomicTypes[0]],
                false,
            ),
        );
        $this->context->addMethod('Invoice', 'find', $query, true);
        self::assertSame($query, $this->resolve(Type::namedObject('Invoice'), 'find', static: true));
    }

    public function testBoundQueryKeepsPreciseOverrides(): void
    {
        $this->context->addClass('InvoiceQuery', ['yii\db\ActiveQuery']);
        $query = Type::fromAtomic(
            new NamedObjectType(
                'InvoiceQuery',
                null,
                null,
                false,
                false,
                [Type::namedObject('yii\db\ActiveQuery', Type::namedObject('Invoice'))->atomicTypes[0]],
                false,
            ),
        );
        $one = Type::namedObject('Invoice');
        $all = Type::list($one);
        $this->context->addMethod('InvoiceQuery', 'one', $one);
        $this->context->addMethod('InvoiceQuery', 'all', $all);
        self::assertSame($one, $this->resolve($query, 'one'));
        self::assertSame($all, $this->resolve($query, 'all'));
    }

    public function testGenericOverridesDeferTemplateSubstitutionToHost(): void
    {
        $this->addGenericQuery('StrictQuery', ['T' => Type::namedObject('yii\db\ActiveRecord')], true);
        $row = Type::fromAtomic(
            new GenericParameterType(
                'T',
                Type::namedObject('yii\db\ActiveRecord'),
                new GenericParent(GenericParentKind::ClassLike, 'strictquery'),
                null,
            ),
        );
        $this->context->addMethod('StrictQuery', 'one', $row);
        $this->context->addMethod('StrictQuery', 'all', Type::list($row));
        foreach (['yii\db\ActiveRecord', 'Invoice'] as $model) {
            $query = Type::namedObject('StrictQuery', Type::namedObject($model));
            self::assertNull($this->resolve($query, 'one'));
            self::assertNull($this->resolve($query, 'all'));
        }
    }

    public function testYiiTemplateContractsStillUseBoundRowSynthesis(): void
    {
        $row = Type::fromAtomic(
            new GenericParameterType(
                'T',
                Type::namedObject('yii\db\ActiveRecord'),
                new GenericParent(GenericParentKind::ClassLike, 'yii\db\activequery'),
                null,
            ),
        );
        $this->context->addMethod('yii\db\ActiveQuery', 'one', Type::union($row, Type::null()));
        $this->context->addMethod('yii\db\ActiveQuery', 'all', Type::array(Type::int(), $row));
        $query = Type::namedObject('yii\db\ActiveQuery', Type::namedObject('Invoice'));
        self::assertSame('Invoice|null', (string) $this->resolve($query, 'one'));
        self::assertSame('array<array-key, Invoice>', (string) $this->resolve($query, 'all'));
    }

    public function testStaticRecordLookupKeepsPreciseOverrides(): void
    {
        $one = Type::namedObject('Invoice');
        $all = Type::list($one);
        $this->context->addMethod('Invoice', 'findOne', $one, true);
        $this->context->addMethod('Invoice', 'findAll', $all, true);
        self::assertNull($this->resolve($one, 'findOne', static: true));
        self::assertNull($this->resolve($one, 'findAll', static: true));
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

    /** @param array<string, Type> $constraints */
    private function addGenericQuery(string $name, array $constraints, bool|int $rowTemplate = false): void
    {
        $this->context->addClass($name, ['yii\db\ActiveQuery']);
        $parent = new GenericParent(GenericParentKind::ClassLike, strtolower($name));
        $templates = [];
        foreach ($constraints as $template => $constraint) {
            $templates[] = new TemplateMetadata($template, $parent, $constraint, null, Variance::Invariant, false);
        }
        $class = $this->context->codebase->getClassLike($name);
        $class = new ClassLikeMetadata(...[...get_object_vars($class), 'templates' => $templates]);
        foreach ([0, 1] as $kind) {
            $this->context->cache->values[(Protocol::GET_CLASS_LIKES << 4) | $kind][strtolower($name)] = $class;
        }
        $symbols = array_map(static fn(TemplateMetadata $template) => Type::fromAtomic(
            new GenericParameterType($template->name, $template->constraint, $parent, null),
        ), $templates);
        foreach ($symbols as $position => $symbol) {
            $this->cacheContainment(
                Type::namedObject($name, ...$symbols),
                Type::namedObject('yii\db\ActiveQuery', $symbol),
                $rowTemplate === true ? $position === 0 : $rowTemplate === $position,
            );
        }
    }

    private function cacheContainment(Type $input, Type $container, bool $contained): void
    {
        $comparison = TypeComparison::containedBy($input, $container);
        $this->context->cache->typeComparisons[$comparison->cacheKey()] = $contained;
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
