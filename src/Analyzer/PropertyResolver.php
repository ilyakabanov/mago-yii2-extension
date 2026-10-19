<?php

declare(strict_types=1);

namespace yii2\extensions\mago\Analyzer;

use Mago\Sdk\Analyzer\Codebase;
use Mago\Sdk\Analyzer\Metadata\MetadataFlags;
use Mago\Sdk\Analyzer\Metadata\PropertyMetadata;
use Mago\Sdk\Analyzer\PropertyType;
use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\NamedObjectType;
use Mago\Sdk\Analyzer\Type\Visibility;

/** Resolves Yii properties using Mago's metadata without executing application code. */
final class PropertyResolver
{
    public static function receiverClass(?Type $receiver): ?string
    {
        if ($receiver === null || count($receiver->atomicTypes) !== 1) {
            return null;
        }

        $atomic = $receiver->atomicTypes[0];

        return $atomic instanceof NamedObjectType ? $atomic->name : null;
    }

    public static function native(Codebase $codebase, string $class, string $name): ?PropertyType
    {
        $metadata = $codebase->getClassLike($class);
        $metadataName = self::propertyName($metadata?->properties ?? [], $name);
        if ($metadataName === null) {
            return null;
        }

        return self::fromMetadata($codebase->getProperty($class, $metadataName));
    }

    /** @param list<string> $excludedClasses */
    public static function magic(
        Codebase $codebase,
        string $class,
        string $name,
        array $excludedClasses = [],
    ): ?PropertyType {
        $visited = [];
        $declaration = self::magicDeclaration(
            $codebase,
            $class,
            $name,
            array_map(strtolower(...), $excludedClasses),
            $visited,
        );
        if ($declaration === null) {
            return null;
        }

        [$owner, $metadataName] = $declaration;
        $declared = $codebase->getMagicProperty($owner, $metadataName);
        if ($owner !== $class) {
            // Query the receiver even though its own annotation list does not contain inherited tags.
            // The host may resolve an inherited template against the receiver's generic parent binding.
            // Mago 1.57 may return T unchanged: its SDK does not expose the class's @extends bindings.
            $inherited = $codebase->getMagicProperty($class, $metadataName);
            if ($inherited !== null && ($declared === null || self::sameDeclaration($inherited, $declared))) {
                return self::fromMetadata($inherited);
            }
        }

        return self::fromMetadata($declared);
    }

    private static function sameDeclaration(PropertyMetadata $inherited, PropertyMetadata $declared): bool
    {
        if ($inherited->location === null || $declared->location === null) {
            return $inherited->location === $declared->location;
        }

        return $inherited->location == $declared->location;
    }

    /**
     * @param list<string> $excludedClasses
     * @param array<string, true> $visited
     * @return array{string, string}|null
     */
    private static function magicDeclaration(
        Codebase $codebase,
        string $class,
        string $name,
        array $excludedClasses,
        array &$visited,
    ): ?array {
        $key = strtolower($class);
        if (isset($visited[$key]) || in_array($key, $excludedClasses, true)) {
            return null;
        }
        $visited[$key] = true;
        $metadata = $codebase->getClassLike($class);
        if ($metadata === null) {
            return null;
        }
        $metadataName = self::propertyName($metadata->magicProperties, $name);
        if ($metadataName !== null) {
            return [$class, $metadataName];
        }

        // Yii's application declarations take precedence: own tags, traits, nearest parent, interfaces.
        $parents = $metadata->directParentClass !== null ? [$metadata->directParentClass] : $metadata->parentClasses;
        foreach ([
            ...$metadata->usedTraits,
            ...$parents,
            ...$metadata->directParentInterfaces,
            ...$metadata->parentInterfaces,
        ] as $ancestor) {
            $declaration = self::magicDeclaration($codebase, $ancestor, $name, $excludedClasses, $visited);
            if ($declaration !== null) {
                return $declaration;
            }
        }

        return null;
    }

    public static function resolve(Codebase $codebase, string $class, string $name): ?PropertyType
    {
        $property = self::native($codebase, $class, $name) ?? self::magic($codebase, $class, $name);
        if ($property !== null) {
            return $property;
        }

        $metadata = $codebase->getClassLike($class);
        if ($metadata === null) {
            return null;
        }

        $read = null;
        $write = null;
        foreach ($metadata->methods as $methodName) {
            if (!in_array(strtolower($methodName), ['get' . strtolower($name), 'set' . strtolower($name)], true)) {
                continue;
            }

            $method = $codebase->getMethod($class, $methodName);
            if ($method === null || $method->static || $method->visibility !== Visibility::Public) {
                continue;
            }

            if (str_starts_with(strtolower($methodName), 'get')) {
                $read = $method->returnType?->type ?? Type::mixed();
            } else {
                $write = ($method->parameters[0] ?? null)?->type?->type ?? Type::mixed();
            }
        }

        return $read === null && $write === null ? null : new PropertyType($read, $write);
    }

    private static function fromMetadata(?PropertyMetadata $property): ?PropertyType
    {
        if ($property === null || $property->flags->contains(MetadataFlags::STATIC)) {
            return null;
        }

        $read = $property->readVisibility === Visibility::Public
        && !$property->flags->contains(MetadataFlags::WRITEONLY)
            ? $property->type?->type ?? Type::mixed()
            : null;
        $write = $property->writeVisibility === Visibility::Public
        && !$property->flags->contains(MetadataFlags::READONLY)
            ? $property->writeType?->type ?? $property->type?->type ?? Type::mixed()
            : null;

        return $read === null && $write === null ? null : new PropertyType($read, $write);
    }

    /** @param list<string> $properties */
    public static function propertyName(array $properties, string $name): ?string
    {
        foreach ($properties as $property) {
            if (ltrim($property, '$') === $name) {
                return $property;
            }
        }

        return null;
    }
}
