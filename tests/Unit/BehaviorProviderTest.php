<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use Mago\Sdk\Analyzer\InvocationKind;
use Mago\Sdk\Analyzer\Metadata\MetadataFlags;
use Mago\Sdk\Analyzer\Metadata\ParameterMetadata;
use Mago\Sdk\Analyzer\Type;
use PHPUnit\Framework\TestCase;
use yii2\extensions\mago\Analyzer\AttributeProvider;
use yii2\extensions\mago\Analyzer\BehaviorMethodProvider;
use yii2\extensions\mago\Analyzer\BehaviorProvider;
use yii2\extensions\mago\ServiceMap;
use yii2\extensions\mago\tests\Support\AnalyzerContext;

final class BehaviorProviderTest extends TestCase
{
    public function testBehaviorPropertyAndMethodAreResolvedFromFrozenMetadata(): void
    {
        $map = new ServiceMap(__DIR__ . '/../fixtures/providers/behaviors.php');
        $context = new AnalyzerContext();
        $context->addClass('app\\Model', ['yii\\base\\Component']);
        $context->addClass('app\\AuditBehavior', magicProperties: ['label'], methods: ['audit']);
        $context->addMagicProperty('app\\AuditBehavior', 'label', Type::string(), true);
        $context->addMethod('app\\AuditBehavior', 'audit', Type::bool());

        $property = (new BehaviorProvider($map))->getPropertyType($context->propertyContext('app\\Model', 'label'));
        self::assertSame('string', (string) $property?->readType);
        self::assertNull($property?->writeType);
        self::assertSame(
            'bool',
            (string) (new BehaviorMethodProvider($map))->getReturnType($context->context(
                Type::namedObject('app\\Model'),
                'audit',
            )),
        );
        self::assertNull((new BehaviorProvider($map))->getPropertyType($context->propertyContext(
            'app\\Model',
            'missing',
        )));
    }

    public function testAttributeInferenceDoesNotTreatReadonlyTagsAsStoredAttributes(): void
    {
        $context = new AnalyzerContext();
        $context->addClass('app\\Record', ['yii\\db\\ActiveRecord'], ['id', 'computed']);
        $context->addMagicProperty('app\\Record', 'id', Type::int());
        $context->addMagicProperty('app\\Record', 'computed', Type::string(), true);
        $provider = new AttributeProvider(new ServiceMap());

        self::assertSame(
            'int',
            (string) $provider->getReturnType($context->context(
                Type::namedObject('app\\Record'),
                'getAttribute',
                [AnalyzerContext::argument(Type::literalString('id'))],
            )),
        );
        self::assertSame(
            'mixed',
            (string) $provider->getReturnType($context->context(
                Type::namedObject('app\\Record'),
                'getAttribute',
                [AnalyzerContext::argument(Type::literalString('computed'))],
            )),
        );
        self::assertSame(
            'mixed',
            (string) $provider->getReturnType($context->context(
                Type::namedObject('app\\Record'),
                'getAttribute',
                [AnalyzerContext::argument(Type::string())],
            )),
        );
    }

    public function testBehaviorGetterAndSignaturePreserveContracts(): void
    {
        $context = new AnalyzerContext();
        $context->addClass('app\\Model', ['yii\\base\\Component']);
        $context->addClass('app\\AuditBehavior', methods: ['getLabel', 'audit']);
        $context->addMethod('app\\AuditBehavior', 'getLabel', Type::string());
        $parameter = new ParameterMetadata(
            'message',
            AnalyzerContext::location(),
            AnalyzerContext::location(),
            null,
            AnalyzerContext::typeMetadata(Type::string()),
            null,
            null,
            null,
            [],
            new MetadataFlags(0),
        );
        $context->addMethod('app\\AuditBehavior', 'audit', Type::bool(), parameters: [$parameter]);
        $map = new ServiceMap(__DIR__ . '/../fixtures/providers/behaviors.php');

        $property = (new BehaviorProvider($map))->getPropertyType($context->propertyContext('app\\Model', 'label'));
        self::assertSame('string', (string) $property?->readType);
        self::assertNull($property?->writeType);

        $signature = (new BehaviorMethodProvider($map))->getCallableSignature($context->signatureContext(
            Type::namedObject('app\\Model'),
            'audit',
        ));
        self::assertSame('$message', $signature?->parameters[0]->name);
        self::assertSame('string', (string) $signature?->parameters[0]->type);
    }

    public function testNativeMembersAndBehaviorMethodInvocationKeepTheirContracts(): void
    {
        $context = new AnalyzerContext();
        $context->addClass('app\\Model', ['yii\\base\\Component'], methods: ['audit'], properties: ['label']);
        $context->addClass('app\\AuditBehavior', magicProperties: ['label'], methods: ['audit', 'staticAudit']);
        $context->addMagicProperty('app\\AuditBehavior', 'label', Type::string());
        $context->addMethod('app\\AuditBehavior', 'audit', Type::bool());
        $context->addMethod('app\\AuditBehavior', 'staticAudit', Type::bool(), static: true);
        $map = new ServiceMap(__DIR__ . '/../fixtures/providers/behaviors.php');

        self::assertNull((new BehaviorProvider($map))->getPropertyType($context->propertyContext(
            'app\\Model',
            'label',
        )));
        $provider = new BehaviorMethodProvider($map);
        self::assertNull($provider->getReturnType($context->context(Type::namedObject('app\\Model'), 'audit')));
        self::assertSame(
            'bool',
            (string) $provider->getReturnType($context->context(Type::namedObject('app\\Model'), 'staticAudit')),
        );
        self::assertNull($provider->getReturnType($context->context(
            Type::namedObject('app\\Model'),
            'staticAudit',
            kind: InvocationKind::StaticMethod,
        )));
    }

    public function testMagoMetadataPropertyNamesIncludeDollarPrefix(): void
    {
        $context = new AnalyzerContext();
        $context->addClass('app\\Model', ['yii\\db\\ActiveRecord'], ['$id']);
        $context->addMagicProperty('app\\Model', '$id', Type::int());
        $context->addClass('app\\AuditBehavior', magicProperties: ['$slug']);
        $context->addMagicProperty('app\\AuditBehavior', '$slug', Type::string());
        $map = new ServiceMap(__DIR__ . '/../fixtures/providers/behaviors.php');

        self::assertSame(
            'string',
            (string) (new BehaviorProvider($map))->getPropertyType($context->propertyContext(
                'app\\Model',
                'slug',
            ))?->readType,
        );
        self::assertSame(
            'int',
            (string) (new AttributeProvider($map))->getReturnType($context->context(
                Type::namedObject('app\\Model'),
                'getAttribute',
                [AnalyzerContext::argument(Type::literalString('id'))],
            )),
        );
        self::assertSame('yii\\db\\BaseActiveRecord', (new AttributeProvider($map))->getTargets()[0]->class);
    }
}
