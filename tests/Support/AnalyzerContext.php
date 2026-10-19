<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Support;

use Closure;
use Mago\Sdk\Analyzer\Argument;
use Mago\Sdk\Analyzer\CallableSignatureProviderContext;
use Mago\Sdk\Analyzer\Codebase;
use Mago\Sdk\Analyzer\Invocation;
use Mago\Sdk\Analyzer\InvocationKind;
use Mago\Sdk\Analyzer\Metadata\ClassLikeKind;
use Mago\Sdk\Analyzer\Metadata\ClassLikeMetadata;
use Mago\Sdk\Analyzer\Metadata\FunctionLikeKind;
use Mago\Sdk\Analyzer\Metadata\FunctionLikeMetadata;
use Mago\Sdk\Analyzer\Metadata\MetadataFlags;
use Mago\Sdk\Analyzer\Metadata\PropertyMetadata;
use Mago\Sdk\Analyzer\Metadata\TypeMetadata;
use Mago\Sdk\Analyzer\PropertyAccess;
use Mago\Sdk\Analyzer\PropertyAccessKind;
use Mago\Sdk\Analyzer\PropertyTypeProviderContext;
use Mago\Sdk\Analyzer\ReturnTypeProviderContext;
use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\FunctionLikeIdentifier;
use Mago\Sdk\Analyzer\Type\NamedObjectType;
use Mago\Sdk\Analyzer\Type\Visibility;
use Mago\Sdk\Analyzer\TypeComparator;
use Mago\Sdk\CancellationTokenInterface;
use Mago\Sdk\Exception\CancelledException;
use Mago\Sdk\Internal\Analyzer\MetadataCache;
use Mago\Sdk\Internal\Analyzer\Protocol;
use Mago\Sdk\Internal\HostClient;
use Mago\Sdk\PHPVersion;
use Mago\Sdk\SourceLocation;
use Mago\Sdk\Span;
use ReflectionClass;

/** An offline SDK context: missing metadata fails instead of silently reaching a native host. */
final class AnalyzerContext
{
    public readonly Codebase $codebase;
    public readonly MetadataCache $cache;
    public readonly TypeComparator $types;
    public readonly CancellationTokenInterface $cancellation;

    public function __construct()
    {
        $host = (new ReflectionClass(HostClient::class))->newInstanceWithoutConstructor();
        $this->cache = new MetadataCache(1);
        $this->cancellation = new class implements CancellationTokenInterface {
            public function isCancelled(): bool
            {
                return false;
            }

            public function throwIfCancelled(): void {}

            public function subscribe(Closure $callback): int
            {
                return 0;
            }

            public function unsubscribe(int $subscription): void {}
        };
        $this->codebase = new Codebase($host, 1, $this->cancellation, $this->cache);
        $this->types = new TypeComparator($host, 1, $this->cancellation, $this->cache);
    }

    /** @param list<string> $parents @param list<string> $magicProperties @param list<string> $methods */
    public function addClass(
        string $class,
        array $parents = [],
        array $magicProperties = [],
        array $methods = [],
        array $properties = [],
    ): void {
        $metadata = new ClassLikeMetadata(
            $class,
            $class,
            ClassLikeKind::Class_,
            self::location(),
            null,
            new MetadataFlags(0),
            $parents[0] ?? null,
            [],
            [],
            $parents,
            [],
            [],
            [],
            [],
            $methods,
            [],
            [],
            $properties,
            $magicProperties,
            [],
            [],
            null,
            null,
            [],
            [],
            [],
            [],
            null,
            null,
            null,
            [],
        );
        foreach ([0, 1] as $kind) {
            $this->cache->values[(Protocol::GET_CLASS_LIKES << 4) | $kind][strtolower($class)] = $metadata;
        }
        foreach (['one', 'all', 'find'] as $method) {
            $this->addMethod($class, $method, null);
        }
    }

    public function addMethod(
        string $class,
        string $method,
        ?Type $returnType,
        bool $static = false,
        array $parameters = [],
    ): void {
        $metadata = $returnType === null
            ? null
            : new FunctionLikeMetadata(
                new FunctionLikeIdentifier(\Mago\Sdk\Analyzer\Type\FunctionLikeKind::Method, $method, $class),
                FunctionLikeKind::Method,
                $method,
                $method,
                self::location(),
                null,
                $parameters,
                null,
                self::typeMetadata($returnType),
                [],
                [],
                [],
                [],
                [],
                [],
                false,
                [],
                true,
                new MetadataFlags(0),
                [],
                Visibility::Public,
                false,
                false,
                $static,
                false,
                [],
            );
        foreach ([Protocol::GET_METHODS, Protocol::GET_DECLARING_METHODS] as $operation) {
            $this->cache->values[$operation << 4][strtolower($class) . "\0" . strtolower($method)] = $metadata;
        }
    }

    public function addMagicProperty(
        string $class,
        string $property,
        Type $type,
        bool $readOnly = false,
        bool $writeOnly = false,
    ): void {
        $this->seedProperty(Protocol::GET_MAGIC_PROPERTIES, $class, $property, $type, $readOnly, $writeOnly);
        $this->seedProperty(Protocol::GET_DECLARING_MAGIC_PROPERTIES, $class, $property, $type, $readOnly, $writeOnly);
    }

    public function addProperty(string $class, string $property, Type $type, bool $readOnly = false): void
    {
        $this->seedProperty(Protocol::GET_PROPERTIES, $class, $property, $type, $readOnly, false);
        $this->seedProperty(Protocol::GET_DECLARING_PROPERTIES, $class, $property, $type, $readOnly, false);
    }

    /** @param list<Argument> $arguments */
    public function context(
        Type $receiver,
        string $method,
        array $arguments = [],
        InvocationKind $kind = InvocationKind::InstanceMethod,
    ): ReturnTypeProviderContext {
        return new ReturnTypeProviderContext(
            PHPVersion::fromParts(8, 3),
            $this->codebase,
            $this->invocation($receiver, $method, $arguments, $kind),
            $this->types,
            $this->cancellation,
        );
    }

    public function propertyContext(
        string $class,
        string $property,
        PropertyAccessKind $kind = PropertyAccessKind::Read,
    ): PropertyTypeProviderContext {
        return new PropertyTypeProviderContext(
            PHPVersion::fromParts(8, 3),
            $this->codebase,
            new PropertyAccess($class, $property, $kind, Type::namedObject($class), new Span(0, 1)),
            $this->types,
            $this->cancellation,
        );
    }

    /** @param list<Argument> $arguments */
    public function signatureContext(
        Type $receiver,
        string $method,
        array $arguments = [],
    ): CallableSignatureProviderContext {
        return new CallableSignatureProviderContext(
            PHPVersion::fromParts(8, 3),
            $this->codebase,
            $this->invocation($receiver, $method, $arguments, InvocationKind::InstanceMethod),
            $this->types,
            $this->cancellation,
        );
    }

    public static function argument(Type $type, ?string $name = null, bool $unpacked = false): Argument
    {
        return new Argument($name, $unpacked, false, new Span(0, 1), '$argument', $type);
    }

    public static function typeMetadata(Type $type): TypeMetadata
    {
        return new TypeMetadata(self::location(), $type, true, false);
    }

    public static function location(): SourceLocation
    {
        return new SourceLocation('fixture.php', new Span(0, 1));
    }

    private function invocation(Type $receiver, string $method, array $arguments, InvocationKind $kind): Invocation
    {
        $atomic = $receiver->atomicTypes[0];
        $class = $atomic instanceof NamedObjectType ? $atomic->name : 'yii\db\ActiveRecord';
        return new Invocation($kind, $method, $class, $receiver, new Span(0, 1), $arguments);
    }

    private function seedProperty(
        int $operation,
        string $class,
        string $property,
        Type $type,
        bool $readOnly,
        bool $writeOnly,
    ): void {
        $flags = ($readOnly ? MetadataFlags::READONLY : 0) | ($writeOnly ? MetadataFlags::WRITEONLY : 0);
        $this->cache->values[$operation << 4][strtolower($class) . "\0" . $property] = new PropertyMetadata(
            $property,
            self::location(),
            null,
            Visibility::Public,
            Visibility::Public,
            null,
            self::typeMetadata($type),
            $readOnly ? null : self::typeMetadata($type),
            null,
            [],
            new MetadataFlags($flags),
            [],
            [],
        );
    }
}
