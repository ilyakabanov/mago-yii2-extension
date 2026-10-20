<?php

declare(strict_types=1);

namespace yii2\extensions\mago\Analyzer;

use Mago\Sdk\Analyzer\Invocation;
use Mago\Sdk\Analyzer\Metadata\FunctionLikeMetadata;
use Mago\Sdk\Analyzer\Metadata\MetadataFlags;
use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\AnyObjectType;
use Mago\Sdk\Analyzer\Type\GenericParameterType;
use Mago\Sdk\Analyzer\Type\GenericParent;
use Mago\Sdk\Analyzer\Type\GenericParentKind;
use Mago\Sdk\Analyzer\Type\KeyedArrayType;
use Mago\Sdk\Analyzer\Type\ListType;
use Mago\Sdk\Analyzer\Type\MixedType;
use Mago\Sdk\Analyzer\Type\NamedObjectType;
use Mago\Sdk\Analyzer\Type\ScalarType;
use Mago\Sdk\Analyzer\Type\ScalarTypeKind;
use Mago\Sdk\Analyzer\Type\SimpleAtomicType;
use Mago\Sdk\Analyzer\TypeComparator;

/** Method-owned templates inferred only from whole parameter types, never from ancestor bindings. */
final class BehaviorMethodTemplates
{
    /** @param array<string, Type> $bindings */
    private function __construct(
        private readonly FunctionLikeMetadata $method,
        private readonly array $bindings,
    ) {}

    /** Conservative callable parameter types when argument inference is unavailable or invalid. */
    public static function bounds(FunctionLikeMetadata $method): ?self
    {
        if ($method->templates === [] || $method->whereConstraints !== []) {
            return null;
        }
        $closed = new self($method, []);
        $constraints = [];
        foreach ($method->templates as $template) {
            if (
                !self::owns($method, $template->definingEntity)
                || isset($constraints[$template->name])
                || $closed->substitute($template->constraint) === null
            ) {
                return null;
            }
            $constraints[$template->name] = $template->constraint;
        }
        return new self($method, $constraints);
    }

    public static function infer(FunctionLikeMetadata $method, Invocation $invocation, TypeComparator $types): ?self
    {
        $bounds = self::bounds($method);
        if ($bounds === null) {
            return null;
        }
        $constraints = $bounds->bindings;
        $closed = new self($method, []);
        $arguments = [];
        $named = false;
        foreach ($invocation->arguments as $position => $argument) {
            // shortcut: unpacked and variadic inference needs native argument expansion; decline until exposed by SDK.
            if ($argument->unpacked || $argument->placeholder || $argument->type === null) {
                return null;
            }
            $index = $position;
            if ($argument->name !== null) {
                $named = true;
                $index = null;
                foreach ($method->parameters as $candidate => $parameter) {
                    if (ltrim($parameter->name, '$') === $argument->name) {
                        $index = $candidate;
                        break;
                    }
                }
            } elseif ($named) {
                return null;
            }
            if ($index === null || !isset($method->parameters[$index]) || isset($arguments[$index])) {
                return null;
            }
            $arguments[$index] = $argument->type;
        }

        $bindings = [];
        foreach ($method->parameters as $index => $parameter) {
            if ($parameter->flags->contains(MetadataFlags::VARIADIC)) {
                return null;
            }
            $actual = $arguments[$index] ?? null;
            if ($actual === null) {
                if (
                    !$parameter->flags->contains(MetadataFlags::HAS_DEFAULT)
                    || $parameter->type !== null && $closed->substitute($parameter->type->type) === null
                ) {
                    return null;
                }
                continue;
            }
            $declared = $parameter->type?->type;
            $atomic = $declared !== null && count($declared->atomicTypes) === 1 ? $declared->atomicTypes[0] : null;
            if (!$atomic instanceof GenericParameterType) {
                if ($declared !== null && $closed->substitute($declared) === null) {
                    return null;
                }
                continue;
            }
            $constraint = $constraints[$atomic->name] ?? null;
            if (
                $constraint === null
                || !self::owns($method, $atomic->definingEntity)
                || $atomic->intersections !== null
                || $closed->substitute($actual) === null
                || !$types->isContainedBy($actual, $constraint)
            ) {
                return null;
            }
            // Differing repeated candidates require native variance/union solving, not a first-argument guess.
            if (isset($bindings[$atomic->name]) && !$types->equals($bindings[$atomic->name], $actual)) {
                return null;
            }
            $bindings[$atomic->name] = $actual;
        }
        if (count($bindings) !== count($constraints)) {
            return null;
        }

        $resolved = new self($method, $bindings);
        if ($method->returnType !== null && $resolved->substitute($method->returnType->type) === null) {
            return null;
        }
        foreach ($method->parameters as $parameter) {
            foreach ([$parameter->type, $parameter->closureThisType, $parameter->outType] as $metadata) {
                if ($metadata !== null && $resolved->substitute($metadata->type) === null) {
                    return null;
                }
            }
        }

        return $resolved;
    }

    public function substitute(Type $type): ?Type
    {
        $members = [];
        foreach ($type->atomicTypes as $atomic) {
            if ($atomic instanceof GenericParameterType) {
                if (!self::owns($this->method, $atomic->definingEntity) || $atomic->intersections !== null) {
                    return null;
                }
                $member = $this->bindings[$atomic->name] ?? null;
                if ($member === null) {
                    return null;
                }
            } elseif ($atomic instanceof ListType && $atomic->knownElements === null) {
                $element = $this->substitute($atomic->elementType);
                if ($element === null) {
                    return null;
                }
                $member = Type::fromAtomic(new ListType($element, null, $atomic->knownCount, $atomic->nonEmpty));
            } elseif ($atomic instanceof KeyedArrayType && $atomic->knownItems === null) {
                $key = $atomic->keyType !== null ? $this->substitute($atomic->keyType) : null;
                $value = $atomic->valueType !== null ? $this->substitute($atomic->valueType) : null;
                if ($key === null || $value === null) {
                    return null;
                }
                $member = Type::fromAtomic(new KeyedArrayType(null, $key, $value, $atomic->nonEmpty));
            } elseif (
                $atomic instanceof NamedObjectType
                && $atomic->intersections === null
                && !$atomic->static
                && !$atomic->isThis
            ) {
                $parameters = [];
                foreach ($atomic->parameters ?? [] as $parameter) {
                    $replacement = $this->substitute($parameter);
                    if ($replacement === null) {
                        return null;
                    }
                    $parameters[] = $replacement;
                }
                $member = Type::fromAtomic(
                    new NamedObjectType(
                        $atomic->name,
                        $atomic->parameters !== null ? $parameters : null,
                        $atomic->variances,
                        $atomic->static,
                        $atomic->isThis,
                        null,
                        $atomic->remappedParameters,
                    ),
                );
            } elseif (
                $atomic instanceof SimpleAtomicType
                || $atomic instanceof MixedType
                || $atomic instanceof AnyObjectType
                || $atomic instanceof ScalarType && $atomic->kind !== ScalarTypeKind::ClassLikeString
            ) {
                $member = Type::fromAtomic($atomic);
            } else {
                return null;
            }
            $members[] = $member;
        }

        $result = array_shift($members);
        foreach ($members as $member) {
            $result = Type::union($result, $member);
        }

        return $result->withFlags($type->flags);
    }

    private static function owns(FunctionLikeMetadata $method, GenericParent $parent): bool
    {
        return (
            $parent->kind === GenericParentKind::FunctionLike
            && strcasecmp($parent->name, $method->identifier->class ?? '') === 0
            && strcasecmp($parent->member ?? '', $method->identifier->name) === 0
        );
    }
}
