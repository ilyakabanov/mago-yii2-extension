<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use Mago\Sdk\Analyzer\Metadata\FunctionLikeMetadata;
use Mago\Sdk\Analyzer\Metadata\MetadataFlags;
use Mago\Sdk\Analyzer\Metadata\ParameterMetadata;
use Mago\Sdk\Analyzer\Metadata\TemplateMetadata;
use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\ConditionalType;
use Mago\Sdk\Analyzer\Type\GenericParameterType;
use Mago\Sdk\Analyzer\Type\GenericParent;
use Mago\Sdk\Analyzer\Type\GenericParentKind;
use Mago\Sdk\Analyzer\Type\Variance;
use Mago\Sdk\Analyzer\Type\Visibility;
use Mago\Sdk\Analyzer\TypeComparison;
use Mago\Sdk\Internal\Analyzer\Protocol;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $this->replaceMethod(
            $context,
            'app\\AuditBehavior',
            new FunctionLikeMetadata(...array_replace(get_object_vars($method), [
                'flags' => new MetadataFlags(MetadataFlags::MAGIC_METHOD),
            ])),
        );
        $provider = $this->provider();

        self::assertSame(
            'string',
            (string) $provider->getReturnType($context->context(Type::namedObject('app\\Model'), 'audit')),
        );
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

        self::assertSame(
            'bool',
            (string) $this->provider()->getReturnType($context->context(Type::namedObject('app\\Model'), 'audit')),
        );
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
        $this->replaceMethod(
            $context,
            'app\\Model',
            new FunctionLikeMetadata(...array_replace(get_object_vars($ownerMethod), [
                'flags' => new MetadataFlags(MetadataFlags::MAGIC_METHOD),
            ])),
        );
        $context->addClass('app\\AuditBehavior', methods: ['audit']);
        $context->addMethod('app\\AuditBehavior', 'audit', Type::string());
        $provider = $this->provider();

        self::assertSame(
            'string',
            (string) $provider->getReturnType($context->context(Type::namedObject('app\\Model'), 'audit')),
        );
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
        $this->replaceMethod(
            $context,
            'app\\AuditBehavior',
            new FunctionLikeMetadata(...array_replace(get_object_vars($method), [
                'visibility' => Visibility::Protected,
            ])),
        );
        $provider = $this->provider();

        self::assertNull($provider->getReturnType($context->context(Type::namedObject('app\\Model'), 'audit')));
        self::assertNull($provider->getCallableSignature($context->signatureContext(
            Type::namedObject('app\\Model'),
            'audit',
        )));
    }

    public function testMethodTemplateUsesDeclaringIdentityAndActualArgument(): void
    {
        $context = $this->context();
        $template = $this->template(owner: 'app\\ParentBehavior');
        $this->genericMethod($context, $template, [$this->parameter('value', $template)]);
        $this->comparison($context, TypeComparison::containedBy(Type::string(), Type::mixed()));
        $argument = AnalyzerContext::argument(Type::string(), 'value');
        $provider = $this->provider();
        $receiver = Type::namedObject('app\\Model');

        self::assertSame(
            'string',
            (string) $provider->getReturnType($context->context($receiver, 'audit', [$argument])),
        );
        self::assertSame(
            'string',
            (string) $provider->getCallableSignature($context->signatureContext(
                $receiver,
                'audit',
                [$argument],
            ))?->parameters[0]->type,
        );
    }

    public function testTemplateSubstitutionPreservesNestedReturnAndParameterContracts(): void
    {
        $context = $this->context();
        $template = $this->template();
        $this->genericMethod(
            $context,
            $template,
            [
                $this->parameter(
                    'value',
                    $template,
                    MetadataFlags::BY_REFERENCE,
                    Type::list($template),
                    Type::namedObject('app\\ClosureContext', $template),
                ),
                $this->parameter('limit', Type::int(), MetadataFlags::HAS_DEFAULT),
            ],
            Type::namedObject('app\\Box', Type::array(Type::string(), Type::list($template))),
        );
        $this->comparison($context, TypeComparison::containedBy(Type::string(), Type::mixed()));
        $arguments = [AnalyzerContext::argument(Type::string())];
        $receiver = Type::namedObject('app\\Model');
        $provider = $this->provider();
        $return = $provider->getReturnType($context->context($receiver, 'audit', $arguments));
        self::assertNotNull($return);
        $box = $return->atomicTypes[0];
        self::assertInstanceOf(\Mago\Sdk\Analyzer\Type\NamedObjectType::class, $box);
        self::assertSame(
            'string',
            (string) $box->parameters[0]->atomicTypes[0]->valueType->atomicTypes[0]->elementType,
        );
        $signature = $provider->getCallableSignature($context->signatureContext($receiver, 'audit', $arguments));
        self::assertNotNull($signature);
        self::assertTrue($signature->parameters[0]->byReference);
        self::assertSame('$value', $signature->parameters[0]->name);
        self::assertSame('string', (string) $signature->parameters[0]->type);
        self::assertSame('string', (string) $signature->parameters[0]->outType->atomicTypes[0]->elementType);
        self::assertSame('string', (string) $signature->parameters[0]->closureThisType->atomicTypes[0]->parameters[0]);
        self::assertSame('int', (string) $signature->parameters[1]->type);
        self::assertTrue($signature->parameters[1]->hasDefault);
    }

    public function testInvalidBoundDoesNotBecomeTheActualParameterType(): void
    {
        $context = $this->context();
        $template = $this->template(Type::string());
        $this->genericMethod($context, $template, [$this->parameter('value', $template)]);
        $this->comparison($context, TypeComparison::containedBy(Type::int(), Type::string()), false);
        $arguments = [AnalyzerContext::argument(Type::int())];
        $receiver = Type::namedObject('app\\Model');
        $provider = $this->provider();

        self::assertNull($provider->getReturnType($context->context($receiver, 'audit', $arguments)));
        self::assertSame(
            'string',
            (string) $provider->getCallableSignature($context->signatureContext(
                $receiver,
                'audit',
                $arguments,
            ))?->parameters[0]->type,
        );
    }

    public function testRepeatedTemplateCandidatesMustAgree(): void
    {
        $context = $this->context();
        $template = $this->template();
        $this->genericMethod($context, $template, [
            $this->parameter('first', $template),
            $this->parameter('second', $template),
        ]);
        $this->comparison($context, TypeComparison::containedBy(Type::string(), Type::mixed()));
        $this->comparison($context, TypeComparison::containedBy(Type::int(), Type::mixed()));
        $this->comparison($context, TypeComparison::equal(Type::string(), Type::string()));
        $this->comparison($context, TypeComparison::equal(Type::string(), Type::int()), false);
        $receiver = Type::namedObject('app\\Model');
        $provider = $this->provider();
        self::assertSame('string', (string) $provider->getReturnType($context->context($receiver, 'audit', [
            AnalyzerContext::argument(Type::string(), 'second'),
            AnalyzerContext::argument(Type::string(), 'first'),
        ])));
        self::assertNull($provider->getReturnType($context->context($receiver, 'audit', [
            AnalyzerContext::argument(Type::string()),
            AnalyzerContext::argument(Type::int()),
        ])));
    }

    #[DataProvider('unsupportedCalls')]
    public function testUnsupportedCallsDoNotInventBindings(string $case): void
    {
        $context = $this->context();
        $template = $this->template();
        $parameters = [$this->parameter('value', $template)];
        $arguments = [AnalyzerContext::argument(Type::string())];
        if ($case === 'missing') {
            $arguments = [];
        } elseif ($case === 'unknown name') {
            $arguments = [AnalyzerContext::argument(Type::string(), 'unknown')];
        } elseif ($case === 'unknown type') {
            $arguments = [new \Mago\Sdk\Analyzer\Argument(null, false, false, new \Mago\Sdk\Span(0, 1), '$x', null)];
        } elseif ($case === 'unpacked') {
            $arguments = [AnalyzerContext::argument(Type::list(Type::string()), unpacked: true)];
        } elseif ($case === 'duplicate') {
            $arguments[] = AnalyzerContext::argument(Type::string(), 'value');
        } elseif ($case === 'excess') {
            $arguments[] = AnalyzerContext::argument(Type::string());
        } elseif ($case === 'positional after named') {
            $arguments = [
                AnalyzerContext::argument(Type::string(), 'value'),
                AnalyzerContext::argument(Type::string()),
            ];
        } elseif ($case === 'variadic') {
            $parameters = [$this->parameter('value', $template, MetadataFlags::VARIADIC)];
        } elseif ($case === 'omitted template default') {
            $parameters[] = $this->parameter('optional', $template, MetadataFlags::HAS_DEFAULT);
        } elseif ($case === 'union parameter') {
            $parameters = [$this->parameter('value', Type::union($template, Type::int()))];
        } elseif ($case === 'nested parameter') {
            $parameters = [$this->parameter('value', Type::list($template))];
        }
        $this->genericMethod($context, $template, $parameters);
        $this->comparison($context, TypeComparison::containedBy(Type::string(), Type::mixed()));

        self::assertNull($this->provider()->getReturnType($context->context(
            Type::namedObject('app\\Model'),
            'audit',
            $arguments,
        )));
    }

    public static function unsupportedCalls(): array
    {
        return array_map(static fn(string $case): array => [$case], [
            'missing',
            'unknown name',
            'unknown type',
            'unpacked',
            'duplicate',
            'excess',
            'positional after named',
            'variadic',
            'omitted template default',
            'union parameter',
            'nested parameter',
        ]);
    }

    public function testIndependentTemplatesAreInferredByIdentityNotPosition(): void
    {
        $context = $this->context();
        $first = $this->template();
        $second = Type::fromAtomic(
            new GenericParameterType(
                'TKey',
                Type::int(),
                new GenericParent(GenericParentKind::FunctionLike, 'app\\AuditBehavior', 'audit'),
                null,
            ),
        );
        $this->genericMethod(
            $context,
            $first,
            [
                $this->parameter('value', $first),
                $this->parameter('key', $second),
            ],
            Type::array($second, $first),
        );
        $method = $context->codebase->getDeclaringMethod('app\\AuditBehavior', 'audit');
        self::assertNotNull($method);
        $this->replaceMethod(
            $context,
            'app\\AuditBehavior',
            new FunctionLikeMetadata(...array_replace(get_object_vars($method), ['templates' => [
                ...$method->templates,
                new TemplateMetadata(
                    'TKey',
                    $second->atomicTypes[0]->definingEntity,
                    Type::int(),
                    null,
                    Variance::Invariant,
                    false,
                ),
            ]])),
        );
        $this->comparison($context, TypeComparison::containedBy(Type::string(), Type::mixed()));
        $this->comparison($context, TypeComparison::containedBy(Type::int(), Type::int()));
        $result = $this->provider()->getReturnType($context->context(Type::namedObject('app\\Model'), 'audit', [
            AnalyzerContext::argument(Type::int(), 'key'),
            AnalyzerContext::argument(Type::string(), 'value'),
        ]));
        self::assertNotNull($result);
        self::assertSame('int', (string) $result->atomicTypes[0]->keyType);
        self::assertSame('string', (string) $result->atomicTypes[0]->valueType);
    }

    public function testConditionalReturnDeclinesRatherThanInventingABinding(): void
    {
        $context = $this->context();
        $template = $this->template();
        $this->genericMethod(
            $context,
            $template,
            [$this->parameter('value', $template)],
            Type::fromAtomic(new ConditionalType($template, Type::string(), Type::int(), Type::bool(), false)),
        );
        $this->comparison($context, TypeComparison::containedBy(Type::string(), Type::mixed()));
        self::assertNull($this->provider()->getReturnType($context->context(
            Type::namedObject('app\\Model'),
            'audit',
            [AnalyzerContext::argument(Type::string())],
        )));
    }

    public function testAnotherMethodsTemplateWithSameNameIsNotSubstituted(): void
    {
        $context = $this->context();
        $template = $this->template();
        $other = Type::fromAtomic(
            new GenericParameterType(
                'T',
                Type::mixed(),
                new GenericParent(GenericParentKind::FunctionLike, 'app\\AuditBehavior', 'other'),
                null,
            ),
        );
        $this->genericMethod($context, $template, [$this->parameter('value', $template)], $other);
        $this->comparison($context, TypeComparison::containedBy(Type::string(), Type::mixed()));
        self::assertNull($this->provider()->getReturnType($context->context(
            Type::namedObject('app\\Model'),
            'audit',
            [AnalyzerContext::argument(Type::string())],
        )));
    }

    public function testClassTemplateWithSameNameIsNotBoundToTheMethodTemplate(): void
    {
        $context = $this->context();
        $template = $this->template();
        $classTemplate = Type::fromAtomic(
            new GenericParameterType(
                'T',
                Type::mixed(),
                new GenericParent(GenericParentKind::ClassLike, 'app\\ParentBehavior'),
                null,
            ),
        );
        $this->genericMethod($context, $template, [$this->parameter('value', $template)], $classTemplate);
        $this->comparison($context, TypeComparison::containedBy(Type::string(), Type::mixed()));

        self::assertNull($this->provider()->getReturnType($context->context(
            Type::namedObject('app\\Model'),
            'audit',
            [AnalyzerContext::argument(Type::string())],
        )));
    }

    private function template(?Type $constraint = null, string $owner = 'app\\AuditBehavior'): Type
    {
        return Type::fromAtomic(
            new GenericParameterType(
                'T',
                $constraint ?? Type::mixed(),
                new GenericParent(GenericParentKind::FunctionLike, $owner, 'audit'),
                null,
            ),
        );
    }

    private function parameter(
        string $name,
        Type $type,
        int $flags = 0,
        ?Type $out = null,
        ?Type $closureThis = null,
    ): ParameterMetadata {
        return new ParameterMetadata(
            $name,
            AnalyzerContext::location(),
            AnalyzerContext::location(),
            null,
            AnalyzerContext::typeMetadata($type),
            $out !== null ? AnalyzerContext::typeMetadata($out) : null,
            $closureThis !== null ? AnalyzerContext::typeMetadata($closureThis) : null,
            null,
            [],
            new MetadataFlags($flags),
        );
    }

    private function genericMethod(
        AnalyzerContext $context,
        Type $template,
        array $parameters,
        ?Type $return = null,
    ): void {
        $atomic = $template->atomicTypes[0];
        self::assertInstanceOf(GenericParameterType::class, $atomic);
        $context->addClass('app\\AuditBehavior');
        $context->addMethod($atomic->definingEntity->name, 'audit', $return ?? $template, parameters: $parameters);
        $method = $context->codebase->getDeclaringMethod($atomic->definingEntity->name, 'audit');
        self::assertNotNull($method);
        $this->replaceMethod(
            $context,
            'app\\AuditBehavior',
            new FunctionLikeMetadata(...array_replace(get_object_vars($method), ['templates' => [new TemplateMetadata(
                'T',
                $atomic->definingEntity,
                $atomic->constraint,
                null,
                Variance::Invariant,
                false,
            )]])),
        );
    }

    private function comparison(AnalyzerContext $context, TypeComparison $comparison, bool $result = true): void
    {
        $context->cache->typeComparisons[$comparison->cacheKey()] = $result;
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
