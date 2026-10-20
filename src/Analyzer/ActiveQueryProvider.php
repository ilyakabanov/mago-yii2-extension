<?php

declare(strict_types=1);

namespace yii2\extensions\mago\Analyzer;

use Mago\Sdk\Analyzer\Codebase;
use Mago\Sdk\Analyzer\InvocationKind;
use Mago\Sdk\Analyzer\Metadata\MetadataFlags;
use Mago\Sdk\Analyzer\MethodReturnTypeProvider;
use Mago\Sdk\Analyzer\MethodTarget;
use Mago\Sdk\Analyzer\ReturnTypeProviderContext;
use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\ArrayItem;
use Mago\Sdk\Analyzer\Type\ArrayKey;
use Mago\Sdk\Analyzer\Type\ArrayKeyKind;
use Mago\Sdk\Analyzer\Type\AtomicType;
use Mago\Sdk\Analyzer\Type\ClassLikeStringType;
use Mago\Sdk\Analyzer\Type\GenericParameterType;
use Mago\Sdk\Analyzer\Type\GenericParentKind;
use Mago\Sdk\Analyzer\Type\IterableType;
use Mago\Sdk\Analyzer\Type\KeyedArrayType;
use Mago\Sdk\Analyzer\Type\ListType;
use Mago\Sdk\Analyzer\Type\MixedType;
use Mago\Sdk\Analyzer\Type\NamedObjectType;
use Mago\Sdk\Analyzer\Type\ScalarType;
use Mago\Sdk\Analyzer\Type\ScalarTypeKind;
use Mago\Sdk\Analyzer\Type\SimpleAtomicType;
use Mago\Sdk\Analyzer\Type\SimpleAtomicTypeKind;

/** Preserves Yii query classes while binding their rows to the queried model. */
final class ActiveQueryProvider implements MethodReturnTypeProvider
{
    private const QUERY = 'yii\db\ActiveQuery';
    private const RECORD = 'yii\db\ActiveRecord';

    private const FLUENT_METHODS = [
        'where',
        'andwhere',
        'orwhere',
        'filterwhere',
        'andfilterwhere',
        'orfilterwhere',
        'filterhaving',
        'andfilterhaving',
        'orfilterhaving',
        'having',
        'andhaving',
        'orhaving',
        'select',
        'addselect',
        'distinct',
        'from',
        'join',
        'innerjoin',
        'leftjoin',
        'rightjoin',
        'joinwith',
        'innerjoinwith',
        'with',
        'orderby',
        'addorderby',
        'groupby',
        'addgroupby',
        'limit',
        'offset',
        'indexby',
        'union',
        'unionorderby',
        'addunionorderby',
        'unionlimit',
        'unionoffset',
        'oncondition',
        'andoncondition',
        'oroncondition',
        'via',
        'viatable',
        'inverseof',
        'alias',
        'cache',
        'nocache',
        'emulateexecution',
    ];

    public function getTargets(): array
    {
        return [
            MethodTarget::allMethods(self::RECORD),
            MethodTarget::allMethods(self::QUERY),
            MethodTarget::allMethods('yii\db\Query'),
            MethodTarget::allMethods('yii\db\ActiveQueryTrait'),
            MethodTarget::allMethods('yii\db\QueryTrait'),
            MethodTarget::exact('yii\base\Component', 'getBehavior'),
            ...array_map(static fn(string $method) => MethodTarget::anyClass($method), self::FLUENT_METHODS),
        ];
    }

    public function getReturnType(ReturnTypeProviderContext $context): ?Type
    {
        $call = $context->invocation;
        $method = strtolower($call->name);
        $receiver = $call->receiverType;
        if ($receiver === null) {
            return null;
        }
        foreach ($call->arguments as $argument) {
            if ($argument->placeholder) {
                return null;
            }
        }

        if ($call->kind === InvocationKind::StaticMethod) {
            return $this->staticReturnType($context, $receiver, $method);
        }

        if (in_array($method, ['hasone', 'hasmany'], true)) {
            $owner = $this->singleNamedObject($receiver);
            if ($owner === null || !$this->isClass($owner->name, self::RECORD, $context->codebase)) {
                return null;
            }
            $class = $call->getArgument(0, 'class');
            if ($class === null || $class->unpacked || $class->type === null) {
                return null;
            }
            $related = $this->classStringObject($class->type);
            return $related === null ? null : Type::namedObject(self::QUERY, $related);
        }

        $query = $this->singleNamedObject($receiver);
        if ($query === null || !$this->isClass($query->name, self::QUERY, $context->codebase)) {
            return null;
        }

        if ($method === 'asarray') {
            if (
                strcasecmp($query->name, self::QUERY) !== 0
                && ($query->parameters ?? []) !== []
                && $this->queryRowType($query, $context) === null
            ) {
                return null;
            }
            foreach ($call->arguments as $argument) {
                if ($argument->unpacked || $argument->placeholder) {
                    if ($argument->unpacked) {
                        $value = TypeTools::booleanArgument($argument);
                        if ($value !== null) {
                            return $value
                                ? $this->queryWithRow($query, Type::array(Type::string(), Type::mixed()), $context)
                                : $this->plainQueryReceiver($receiver, $query);
                        }
                    }
                    return null;
                }
            }
            $value = $call->getArgument(0, 'value');
            if ($value?->type?->getLiteralBool() === false) {
                return $this->plainQueryReceiver($receiver, $query);
            }
            $row = $this->queryRowType($query, $context);
            $arrayRow = $this->arrayRowType($row, $context->codebase);
            if ($value !== null && $value->type?->getLiteralBool() !== true) {
                $arrayRow = Type::union($row ?? Type::mixed(), $arrayRow);
            }
            return $this->queryWithRow($query, $arrayRow, $context);
        }

        if (in_array($method, self::FLUENT_METHODS, true)) {
            return $this->plainQueryReceiver($receiver, $query);
        }
        if ($method === 'getbehavior') {
            return Type::union(
                Type::namedObject('yii\base\Behavior', $this->plainQueryReceiver($receiver, $query)),
                Type::null(),
            );
        }
        // Scalar/column/count/execution methods already have row-independent Yii PHPDoc types.
        if (!in_array($method, ['all', 'one', 'batch', 'each'], true)) {
            return null;
        }

        $row = $this->queryRowType($query, $context);
        if ($row === null) {
            if ($method === 'all') {
                $native = $context->codebase->getDeclaringMethod($query->name, 'all')?->returnType?->type;
                $arrays = array_filter(
                    $native?->atomicTypes ?? [],
                    static fn($atomic) => $atomic instanceof KeyedArrayType || $atomic instanceof ListType,
                );
                $precise = array_filter($arrays, static function ($atomic): bool {
                    if (!$atomic instanceof KeyedArrayType && !$atomic instanceof ListType) {
                        return false;
                    }
                    $value = $atomic instanceof ListType ? $atomic->elementType : $atomic->valueType;
                    return $value !== null && !$value->atomicTypes[0] instanceof MixedType;
                });
                if ($precise !== [] && count($arrays) > count($precise)) {
                    $other = array_filter(
                        $native?->atomicTypes ?? [],
                        static fn($atomic) => !$atomic instanceof KeyedArrayType && !$atomic instanceof ListType,
                    );
                    return Type::fromAtomics(...array_values([...$precise, ...$other]));
                }
            }
            return null;
        }

        // Custom overrides with precise row contracts remain authoritative.
        if (in_array($method, ['one', 'all'], true)) {
            $metadata = $context->codebase->getDeclaringMethod($query->name, $method);
            $native = $metadata?->returnType?->type;
            $owner = $metadata?->identifier->class;
            if (
                $native !== null
                && $owner !== null
                && strcasecmp($owner, self::QUERY) !== 0
                && $this->containsOnlyTemplateRows($native, $method, $owner)
            ) {
                // Only the host can substitute a custom override's declaring-owner templates.
                return null;
            }
            if ($native !== null && $this->containsOnlyModelRows($native, $method, $row, $context->codebase)) {
                return $native;
            }
        }

        return match ($method) {
            'one' => Type::union($row, Type::null()),
            'all' => Type::array(self::arrayKey(), $row),
            'batch' => Type::namedObject('yii\db\BatchQueryResult', Type::int(), Type::array(self::arrayKey(), $row)),
            'each' => Type::namedObject('yii\db\BatchQueryResult', self::arrayKey(), $row),
        };
    }

    private function staticReturnType(ReturnTypeProviderContext $context, Type $receiver, string $method): ?Type
    {
        $model = $this->singleNamedObject($receiver);
        $fromClassString = $model === null;
        if ($model === null) {
            $model = $this->singleNamedObject($this->classStringObject($receiver) ?? Type::mixed());
        }
        if ($model === null || !$this->isClass($model->name, self::RECORD, $context->codebase)) {
            return null;
        }
        $modelType = Type::fromAtomic(
            new NamedObjectType(
                $model->name,
                $model->parameters,
                $model->variances,
                $fromClassString ? false : $model->static,
                false,
                $model->intersections,
                $model->remappedParameters,
            ),
        );
        if (in_array($method, ['findone', 'findall'], true)) {
            $metadata = $context->codebase->getDeclaringMethod($model->name, $method);
            $owner = $metadata?->identifier->class;
            if (
                $owner !== null
                && !in_array(strtolower($owner), ['yii\db\activerecord', 'yii\db\baseactiverecord'], true)
            ) {
                return null;
            }
            return $method === 'findone'
                ? Type::union($modelType, Type::null())
                : Type::array(self::arrayKey(), $modelType);
        }
        $metadata = $context->codebase->getDeclaringMethod($model->name, $method);
        $declared = $metadata?->returnType?->type;
        if ($declared === null) {
            return in_array($method, ['find', 'findbysql'], true) ? Type::namedObject(self::QUERY, $modelType) : null;
        }
        $query = $this->singleNamedObject($declared);
        if ($query === null || !$this->isClass($query->name, self::QUERY, $context->codebase)) {
            return null;
        }
        if (strcasecmp($query->name, self::QUERY) === 0) {
            return Type::namedObject(self::QUERY, $modelType);
        }
        $bound = $this->queryRowType($query, $context);
        if ($bound === null && ($query->parameters ?? []) !== []) {
            return null;
        }
        if ($bound !== null && !$this->isUnboundRecord($bound)) {
            $boundObject = $this->singleNamedObject($bound);
            if (
                $boundObject !== null
                && $boundObject->static
                && !$model->static
                && strcasecmp($model->name, $boundObject->name) === 0
                && $this->hasSingleRowTemplate($query, $context)
            ) {
                return $this->queryWithRow($query, $modelType, $context);
            }
            if (
                $boundObject === null
                || ($query->parameters ?? []) !== [] && strcasecmp($model->name, $boundObject->name) === 0
                || !$this->isClass($model->name, $boundObject->name, $context->codebase)
            ) {
                return $declared;
            }
        }
        return Type::fromAtomic(
            new NamedObjectType(
                $query->name,
                $query->parameters,
                $query->variances,
                $query->static,
                $query->isThis,
                [...($query->intersections ?? []), Type::namedObject(self::QUERY, $modelType)->atomicTypes[0]],
                $query->remappedParameters,
            ),
        );
    }

    private function queryRowType(NamedObjectType $query, ReturnTypeProviderContext $context): ?Type
    {
        $codebase = $context->codebase;
        foreach ($query->intersections ?? [] as $intersection) {
            if (
                $intersection instanceof NamedObjectType
                && strcasecmp($intersection->name, self::QUERY) === 0
                && isset($intersection->parameters[0])
            ) {
                return $intersection->parameters[0];
            }
        }
        if (strcasecmp($query->name, self::QUERY) === 0) {
            return $query->parameters[0] ?? null;
        }
        if (($query->parameters ?? []) !== []) {
            return $this->hasSingleRowTemplate($query, $context) ? $query->parameters[0] ?? null : null;
        }
        $class = $codebase->getClassLike($query->name);
        if ($class === null || $class->templates !== [] || !$this->isClass($query->name, self::QUERY, $codebase)) {
            return null;
        }
        $candidates = [];
        foreach (['one', 'all'] as $method) {
            $declaration = $codebase->getDeclaringMethod($query->name, $method);
            if ($declaration === null || $declaration->identifier->class === self::QUERY) {
                continue;
            }
            $return = $declaration->returnType?->type;
            if ($return === null) {
                return null;
            }
            $classes = $this->declaredRowClasses($return, $method, $codebase);
            if ($classes === null) {
                return null;
            }
            foreach ($classes as $name) {
                $candidates[strtolower($name)] = $name;
            }
        }
        return count($candidates) === 1 ? Type::namedObject(array_values($candidates)[0]) : null;
    }

    /** @return list<string>|null */
    private function declaredRowClasses(Type $type, string $method, Codebase $codebase): ?array
    {
        $rows = [];
        foreach ($type->atomicTypes as $atomic) {
            if ($method === 'all') {
                $element = match (true) {
                    $atomic instanceof KeyedArrayType => $atomic->valueType ?? $this->shapeValues($atomic),
                    $atomic instanceof ListType => $atomic->elementType,
                    default => null,
                };
                if ($element === null) {
                    return null;
                }
                if (count($element->atomicTypes) === 1 && $element->atomicTypes[0] instanceof MixedType) {
                    continue;
                }
                foreach ($element->atomicTypes as $row) {
                    $rows[] = $row;
                }
            } elseif (!$this->isNull($atomic)) {
                $rows[] = $atomic;
            }
        }
        $classes = [];
        foreach ($rows as $row) {
            if ($row instanceof KeyedArrayType || $row instanceof ListType) {
                continue;
            }
            if (!$row instanceof NamedObjectType) {
                return null;
            }
            if (in_array(
                strtolower($row->name),
                ['yii\db\activerecord', 'yii\base\model', 'yii\base\component', 'yii\base\baseobject'],
                true,
            )) {
                continue;
            }
            if (!$this->isClass($row->name, self::RECORD, $codebase)) {
                return null;
            }
            $classes[] = $row->name;
        }
        return $classes === [] ? null : $classes;
    }

    private function arrayRowType(?Type $row, Codebase $codebase): Type
    {
        $fallback = Type::array(Type::string(), Type::mixed());
        if ($row === null) {
            return $fallback;
        }
        $arrays = array_filter(
            $row->atomicTypes,
            static fn($atomic) => $atomic instanceof KeyedArrayType || $atomic instanceof ListType,
        );
        if (count($arrays) === count($row->atomicTypes)) {
            return $row;
        }
        $model = $this->singleNamedObject($row);
        if ($model === null) {
            return $fallback;
        }
        $class = $codebase->getClassLike($model->name);
        if ($class === null) {
            return $fallback;
        }
        $properties = $this->propertyDeclarations($model->name, $codebase);
        $items = [];
        foreach ($properties as [$owner, $name]) {
            $property = $codebase->getMagicProperty($owner, $name);
            if ($property === null || $property->flags->contains(MetadataFlags::WRITEONLY)) {
                continue;
            }
            $value = $property->type?->type;
            if ($value === null) {
                continue;
            }
            $optional = $property->flags->contains(MetadataFlags::READONLY);
            $related = $this->relationRow($value, $codebase);
            if ($related !== null) {
                $value = $related;
                $optional = true;
            }
            $items[] = new ArrayItem(new ArrayKey(ArrayKeyKind::String, ltrim($name, '$')), $optional, $value);
        }
        $nonEmpty = count(array_filter($items, static fn(ArrayItem $item) => !$item->optional)) > 0;
        return $items === [] ? $fallback : Type::fromAtomic(new KeyedArrayType($items, null, null, $nonEmpty));
    }

    private function relationRow(Type $type, Codebase $codebase): ?Type
    {
        $rows = array_values(array_filter($type->atomicTypes, fn($atomic) => !$this->isNull($atomic)));
        if ($rows === []) {
            return null;
        }
        $many = true;
        $objects = [];
        foreach ($rows as $row) {
            if (
                $row instanceof KeyedArrayType
                || $row instanceof ListType
                || $row instanceof IterableType
                || $row instanceof NamedObjectType
                && !$this->isClass($row->name, self::RECORD, $codebase)
                && $this->isClass($row->name, 'Traversable', $codebase)
            ) {
                $element = match (true) {
                    $row instanceof ListType => $row->elementType,
                    $row instanceof IterableType => $row->valueType,
                    $row instanceof NamedObjectType => $row->parameters[count($row->parameters ?? []) - 1] ?? null,
                    default => $row->valueType ?? $this->shapeValues($row),
                };
                if ($element === null) {
                    return null;
                }
                foreach ($element->atomicTypes as $atomic) {
                    if (!$this->isNull($atomic)) {
                        $objects[] = $atomic;
                    }
                }
            } else {
                $many = false;
                $objects[] = $row;
            }
        }
        foreach ($objects as $object) {
            if (
                !$object instanceof NamedObjectType
                || !$this->isClass($object->name, self::RECORD, $codebase)
                && !$this->isClass($object->name, 'yii\db\ActiveRecordInterface', $codebase)
            ) {
                return null;
            }
        }
        if ($objects === []) {
            return null;
        }
        $row = Type::array(Type::string(), Type::mixed());
        return $many ? Type::array(self::arrayKey(), $row) : Type::union($row, Type::null());
    }

    /**
     * @param array<string, true> $visited
     * @return array<string, array{string, string}>
     */
    private function propertyDeclarations(string $name, Codebase $codebase, array &$visited = []): array
    {
        if (
            isset($visited[strtolower($name)])
            || in_array(
                strtolower($name),
                [
                    'yii\db\activerecord',
                    'yii\db\baseactiverecord',
                    'yii\db\activerecordinterface',
                    'yii\base\model',
                    'yii\base\component',
                    'yii\base\baseobject',
                ],
                true,
            )
        ) {
            return [];
        }
        $visited[strtolower($name)] = true;
        $class = $codebase->getClassLike($name);
        if ($class === null) {
            return [];
        }
        $properties = [];
        foreach ($class->magicProperties as $property) {
            $properties[ltrim($property, '$')] = [$class->name, $property];
        }
        $ancestors = [...$class->usedTraits];
        if ($class->directParentClass !== null) {
            $ancestors[] = $class->directParentClass;
        }
        $ancestors = [...$ancestors, ...$class->directParentInterfaces];
        foreach ($ancestors as $ancestor) {
            $properties += $this->propertyDeclarations($ancestor, $codebase, $visited);
        }
        return $properties;
    }

    private function plainQueryReceiver(Type $receiver, NamedObjectType $query): Type
    {
        if (!$query->static && !$query->isThis) {
            return $receiver;
        }
        return Type::fromAtomic(
            new NamedObjectType(
                $query->name,
                $query->parameters,
                $query->variances,
                false,
                false,
                $query->intersections,
                $query->remappedParameters,
            ),
        );
    }

    private function hasSingleRowTemplate(NamedObjectType $query, ReturnTypeProviderContext $context): bool
    {
        // shortcut: multi-template queries keep native inference until their row positions can be validated.
        $class = $context->codebase->getClassLike($query->name);
        if (count($query->parameters ?? []) !== 1 || $class === null || count($class->templates) !== 1) {
            return false;
        }
        $template = $class->templates[0];
        if (
            $template->definingEntity->kind !== GenericParentKind::ClassLike
            || strcasecmp($template->definingEntity->name, $query->name) !== 0
        ) {
            return false;
        }
        $symbol = Type::fromAtomic(
            new GenericParameterType($template->name, $template->constraint, $template->definingEntity, null),
        );
        // Concrete rows can coincide with an unused template's bound; compare the symbolic binding instead.
        return $context->types->isContainedBy(
            Type::namedObject($query->name, $symbol),
            Type::namedObject(self::QUERY, $symbol),
        );
    }

    private function queryWithRow(NamedObjectType $query, Type $row, ReturnTypeProviderContext $context): Type
    {
        if (strcasecmp($query->name, self::QUERY) === 0 || $this->hasSingleRowTemplate($query, $context)) {
            return Type::namedObject($query->name, $row);
        }
        return Type::namedObject(self::QUERY, $row);
    }

    private function singleNamedObject(Type $type): ?NamedObjectType
    {
        $objects = array_values(array_filter($type->atomicTypes, fn($atomic) => !$this->isNull($atomic)));
        return count($objects) === 1 && $objects[0] instanceof NamedObjectType ? $objects[0] : null;
    }

    private function classStringObject(Type $type): ?Type
    {
        $literal = $type->getLiteralString();
        if ($literal !== null && $literal !== '') {
            return Type::namedObject($literal);
        }
        $atomic = $type->atomicTypes[0];
        if (
            count($type->atomicTypes) === 1
            && $atomic instanceof ScalarType
            && $atomic->refinement instanceof ClassLikeStringType
        ) {
            return $atomic->refinement->constraint === null ? null : Type::fromAtomic($atomic->refinement->constraint);
        }
        return null;
    }

    private function isClass(string $class, string $parent, Codebase $codebase): bool
    {
        if (strcasecmp($class, $parent) === 0) {
            return true;
        }
        $metadata = $codebase->getClassLike($class);
        if ($metadata === null) {
            return false;
        }
        foreach ([...$metadata->parentClasses, ...$metadata->parentInterfaces] as $ancestor) {
            if (strcasecmp($ancestor, $parent) === 0) {
                return true;
            }
        }
        return false;
    }

    private function isUnboundRecord(Type $type): bool
    {
        foreach ($type->atomicTypes as $atomic) {
            if (
                $atomic instanceof MixedType
                || $this->isNull($atomic)
                || $atomic instanceof KeyedArrayType
                || $atomic instanceof ListType
            ) {
                continue;
            }
            if ($atomic instanceof GenericParameterType && $this->isUnboundRecord($atomic->constraint)) {
                continue;
            }
            if (!$atomic instanceof NamedObjectType || strcasecmp($atomic->name, self::RECORD) !== 0) {
                return false;
            }
        }
        return true;
    }

    private function containsOnlyTemplateRows(Type $type, string $method, string $owner): bool
    {
        foreach ($type->atomicTypes as $atomic) {
            if ($method === 'all') {
                $value = match (true) {
                    $atomic instanceof ListType => $atomic->elementType,
                    $atomic instanceof KeyedArrayType => $atomic->valueType,
                    default => null,
                };
                if ($value === null || !$this->containsOnlyTemplateRows($value, 'one', $owner)) {
                    return false;
                }
            } elseif (!$this->isNull($atomic)) {
                if (
                    !$atomic instanceof GenericParameterType
                    || $atomic->definingEntity->kind !== GenericParentKind::ClassLike
                    || strcasecmp($atomic->definingEntity->name, $owner) !== 0
                ) {
                    return false;
                }
            }
        }
        return true;
    }

    private function containsOnlyModelRows(Type $type, string $method, Type $model, Codebase $codebase): bool
    {
        foreach ($type->atomicTypes as $atomic) {
            if ($method === 'all') {
                $value = match (true) {
                    $atomic instanceof ListType => $atomic->elementType,
                    $atomic instanceof KeyedArrayType => $atomic->valueType,
                    default => null,
                };
                if ($value === null || !$this->containsOnlyModelRows($value, 'one', $model, $codebase)) {
                    return false;
                }
            } elseif (!$this->isNull($atomic)) {
                $modelObject = $this->singleNamedObject($model);
                if (
                    !$atomic instanceof NamedObjectType
                    || $modelObject === null
                    || !$this->isClass($atomic->name, $modelObject->name, $codebase)
                ) {
                    return false;
                }
            }
        }
        return true;
    }

    private function shapeValues(KeyedArrayType $array): ?Type
    {
        $values = array_map(static fn(ArrayItem $item) => $item->type, $array->knownItems ?? []);
        $first = array_shift($values);
        if ($first === null) {
            return null;
        }
        $second = array_shift($values);
        return $second === null ? $first : Type::union($first, $second, ...$values);
    }

    private function isNull(AtomicType $type): bool
    {
        return $type instanceof SimpleAtomicType && $type->kind === SimpleAtomicTypeKind::Null;
    }

    private static function arrayKey(): Type
    {
        return Type::fromAtomic(new ScalarType(ScalarTypeKind::ArrayKey));
    }
}
