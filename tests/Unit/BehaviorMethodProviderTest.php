<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use Mago\Sdk\Analyzer\Metadata\FunctionLikeMetadata;
use Mago\Sdk\Analyzer\Metadata\MetadataFlags;
use Mago\Sdk\Analyzer\Metadata\ParameterMetadata;
use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\Visibility;
use Mago\Sdk\Internal\Analyzer\Protocol;
use PHPUnit\Framework\TestCase;
use yii2\extensions\mago\Analyzer\BehaviorMethodProvider;
use yii2\extensions\mago\ServiceMap;
use yii2\extensions\mago\tests\Support\AnalyzerContext;

final class BehaviorMethodProviderTest extends TestCase
{
    public function testDocumentedBehaviorMethodDoesNotRequireANativeMethodListEntry(): void
    {
        $context = $this->context();
        $context->addClass('app\\AuditBehavior');
        $parameter = new ParameterMetadata(
            'id',
            AnalyzerContext::location(),
            AnalyzerContext::location(),
            null,
            AnalyzerContext::typeMetadata(Type::int()),
            null,
            null,
            null,
            [],
            new MetadataFlags(0),
        );
        $context->addMethod('app\\AuditBehavior', 'audit', Type::string(), parameters: [$parameter]);
        $method = $context->codebase->getMethod('app\\AuditBehavior', 'audit');
        self::assertNotNull($method);
        $this->replaceMethod($context, 'app\\AuditBehavior', new FunctionLikeMetadata(...array_replace(
            get_object_vars($method),
            ['flags' => new MetadataFlags(MetadataFlags::MAGIC_METHOD)],
        )));
        $provider = $this->provider();

        self::assertSame('string', (string) $provider->getReturnType($context->context(
            Type::namedObject('app\\Model'),
            'audit',
        )));
        $signature = $provider->getCallableSignature($context->signatureContext(
            Type::namedObject('app\\Model'),
            'audit',
        ));
        self::assertSame('$id', $signature?->parameters[0]->name);
        self::assertSame('int', (string) $signature?->parameters[0]->type);
    }

    public function testInheritedBehaviorMethodIsResolvedFromDeclaringMethodMetadata(): void
    {
        $context = $this->context();
        $context->addClass('app\\AuditBehavior', ['app\\ParentBehavior']);
        $context->addMethod('app\\AuditBehavior', 'audit', Type::bool());
        $context->cache->values[Protocol::GET_METHODS << 4]["app\\auditbehavior\0audit"] = null;

        self::assertSame('bool', (string) $this->provider()->getReturnType($context->context(
            Type::namedObject('app\\Model'),
            'audit',
        )));
    }

    public function testInheritedNativeOwnerMethodKeepsBothNativeContracts(): void
    {
        $context = $this->context();
        $context->addMethod('app\\Model', 'audit', Type::int());
        $context->cache->values[Protocol::GET_METHODS << 4]["app\\model\0audit"] = null;
        $context->addClass('app\\AuditBehavior', methods: ['audit']);
        $context->addMethod('app\\AuditBehavior', 'audit', Type::string());
        $provider = $this->provider();

        self::assertNull($provider->getReturnType($context->context(Type::namedObject('app\\Model'), 'audit')));
        self::assertNull($provider->getCallableSignature($context->signatureContext(
            Type::namedObject('app\\Model'),
            'audit',
        )));
    }

    public function testOwnerPhpDocMethodDoesNotHideTheBehaviorMethod(): void
    {
        $context = $this->context();
        $context->addClass('app\\Model', ['yii\\base\\Component'], methods: ['audit']);
        $context->addMethod('app\\Model', 'audit', Type::int());
        $ownerMethod = $context->codebase->getMethod('app\\Model', 'audit');
        self::assertNotNull($ownerMethod);
        $this->replaceMethod($context, 'app\\Model', new FunctionLikeMetadata(...array_replace(
            get_object_vars($ownerMethod),
            ['flags' => new MetadataFlags(MetadataFlags::MAGIC_METHOD)],
        )));
        $context->addClass('app\\AuditBehavior', methods: ['audit']);
        $context->addMethod('app\\AuditBehavior', 'audit', Type::string());
        $provider = $this->provider();

        self::assertSame('string', (string) $provider->getReturnType($context->context(
            Type::namedObject('app\\Model'),
            'audit',
        )));
        self::assertNotNull($provider->getCallableSignature($context->signatureContext(
            Type::namedObject('app\\Model'),
            'audit',
        )));
    }

    public function testNonPublicBehaviorMethodIsNotForwarded(): void
    {
        $context = $this->context();
        $context->addClass('app\\AuditBehavior', methods: ['audit']);
        $context->addMethod('app\\AuditBehavior', 'audit', Type::string());
        $method = $context->codebase->getMethod('app\\AuditBehavior', 'audit');
        self::assertNotNull($method);
        $this->replaceMethod($context, 'app\\AuditBehavior', new FunctionLikeMetadata(...array_replace(
            get_object_vars($method),
            ['visibility' => Visibility::Protected],
        )));
        $provider = $this->provider();

        self::assertNull($provider->getReturnType($context->context(Type::namedObject('app\\Model'), 'audit')));
        self::assertNull($provider->getCallableSignature($context->signatureContext(
            Type::namedObject('app\\Model'),
            'audit',
        )));
    }

    private function context(): AnalyzerContext
    {
        $context = new AnalyzerContext();
        $context->addClass('app\\Model', ['yii\\base\\Component']);
        $context->addMethod('app\\Model', 'audit', null);

        return $context;
    }

    private function provider(): BehaviorMethodProvider
    {
        return new BehaviorMethodProvider(new ServiceMap(__DIR__ . '/../fixtures/providers/behaviors.php'));
    }

    private function replaceMethod(AnalyzerContext $context, string $class, FunctionLikeMetadata $method): void
    {
        foreach ([Protocol::GET_METHODS, Protocol::GET_DECLARING_METHODS] as $operation) {
            $context->cache->values[$operation << 4][strtolower($class) . "\0" . strtolower($method->name)] = $method;
        }
    }
}
