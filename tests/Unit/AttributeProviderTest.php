<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use Mago\Sdk\Analyzer\Metadata\ClassLikeMetadata;
use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\GenericParameterType;
use Mago\Sdk\Analyzer\Type\GenericParent;
use Mago\Sdk\Analyzer\Type\GenericParentKind;
use Mago\Sdk\Internal\Analyzer\Protocol;
use PHPUnit\Framework\TestCase;
use yii2\extensions\mago\Analyzer\AttributeProvider;
use yii2\extensions\mago\Analyzer\PropertyResolver;
use yii2\extensions\mago\ServiceMap;
use yii2\extensions\mago\tests\Support\AnalyzerContext;

final class AttributeProviderTest extends TestCase
{
    private function attribute(AnalyzerContext $context, string $class, string $name): ?Type
    {
        return (new AttributeProvider(new ServiceMap()))->getReturnType($context->context(
            Type::namedObject($class),
            'getAttribute',
            [AnalyzerContext::argument(Type::literalString($name))],
        ));
    }

    /** @param list<string> $parents @param list<string> $traits @param list<string> $interfaces */
    private function hierarchy(
        AnalyzerContext $context,
        string $class,
        array $parents = [],
        array $traits = [],
        array $interfaces = [],
    ): void {
        $context->addClass($class, $parents);
        $values = get_object_vars($context->codebase->getClassLike($class));
        $values['usedTraits'] = $traits;
        $values['directParentInterfaces'] = $interfaces;
        $values['parentInterfaces'] = $interfaces;
        $metadata = new ClassLikeMetadata(...$values);
        foreach ([0, 1] as $kind) {
            $context->cache->values[(Protocol::GET_CLASS_LIKES << 4) | $kind][strtolower($class)] = $metadata;
        }
    }

    private function absentReceiverLookup(AnalyzerContext $context, string $class, string $name): void
    {
        $context->cache->values[Protocol::GET_MAGIC_PROPERTIES << 4][strtolower($class) . "\0" . $name] = null;
    }

    public function testInheritedTagsAreResolvedEvenWhenReceiverOwnListIsEmpty(): void
    {
        $context = new AnalyzerContext();
        $context->addClass('app\\Article', ['app\\ArticleRecord']);
        $context->addClass('app\\ArticleRecord', ['yii\\db\\ActiveRecord'], ['$id', '$title']);
        foreach (['$id' => Type::int(), '$title' => Type::string()] as $name => $type) {
            $context->addMagicProperty('app\\ArticleRecord', $name, $type);
            $this->absentReceiverLookup($context, 'app\\Article', $name);
        }

        self::assertSame('int', (string) $this->attribute($context, 'app\\Article', 'id'));
        self::assertSame('string', (string) $this->attribute($context, 'app\\Article', 'title'));
    }

    public function testPrecedenceIsOwnThenTraitThenNearestParentThenInterface(): void
    {
        $context = new AnalyzerContext();
        $this->hierarchy($context, 'app\\Draft', ['app\\Record'], ['app\\Revision'], ['app\\Publishable']);
        $context->addClass('app\\Record', ['app\\Ancestor'], ['$revision', '$id']);
        $context->addClass('app\\Ancestor', magicProperties: ['$id']);
        $context->addClass('app\\Revision', magicProperties: ['$revision']);
        $context->addClass('app\\Publishable', magicProperties: ['$slug', '$id']);
        $context->addMagicProperty('app\\Record', '$revision', Type::string());
        $context->addMagicProperty('app\\Record', '$id', Type::int());
        $context->addMagicProperty('app\\Ancestor', '$id', Type::string());
        $context->addMagicProperty('app\\Revision', '$revision', Type::int());
        $context->addMagicProperty('app\\Publishable', '$slug', Type::string());
        $context->addMagicProperty('app\\Publishable', '$id', Type::bool());
        foreach (['$revision', '$id', '$slug'] as $name) {
            $this->absentReceiverLookup($context, 'app\\Draft', $name);
        }

        self::assertSame('int', (string) $this->attribute($context, 'app\\Draft', 'revision'));
        self::assertSame('int', (string) $this->attribute($context, 'app\\Draft', 'id'));
        self::assertSame('string', (string) $this->attribute($context, 'app\\Draft', 'slug'));

        $context->addClass('app\\Draft', ['app\\Record'], ['$revision']);
        $context->addMagicProperty('app\\Draft', '$revision', Type::bool());
        self::assertSame('bool', (string) $this->attribute($context, 'app\\Draft', 'revision'));
    }

    public function testNearestReadonlyAndWriteonlyTagsDoNotFallThroughToWritableParents(): void
    {
        $context = new AnalyzerContext();
        $context->addClass('app\\Record', magicProperties: ['$computed', '$password']);
        $context->addClass('app\\Draft', ['app\\Record'], ['$computed', '$password']);
        $context->addMagicProperty('app\\Record', '$computed', Type::int());
        $context->addMagicProperty('app\\Record', '$password', Type::string());
        $context->addMagicProperty('app\\Draft', '$computed', Type::string(), readOnly: true);
        $context->addMagicProperty('app\\Draft', '$password', Type::string(), writeOnly: true);

        self::assertSame('mixed', (string) $this->attribute($context, 'app\\Draft', 'computed'));
        self::assertSame('mixed', (string) $this->attribute($context, 'app\\Draft', 'password'));
        self::assertNull(PropertyResolver::magic($context->codebase, 'app\\Draft', 'password')?->readType);
        self::assertNull(PropertyResolver::magic($context->codebase, 'app\\Draft', 'computed')?->writeType);
    }

    public function testGetAttributeDoesNotInvokeVirtualGetters(): void
    {
        $record = new class extends \yii\db\ActiveRecord {
            public int $getterCalls = 0;

            public function attributes(): array
            {
                return [];
            }

            public function getLabel(): string
            {
                ++$this->getterCalls;

                return 'visible';
            }
        };

        self::assertSame('visible', $record->label);
        self::assertSame(1, $record->getterCalls);
        self::assertNull($record->getAttribute('label'));
        self::assertSame(1, $record->getterCalls);
    }

    public function testFrameworkVirtualPropertiesAreNotAttributes(): void
    {
        $context = new AnalyzerContext();
        $context->addClass('app\\Record', ['yii\\db\\ActiveRecord']);
        $context->addClass('yii\\db\\ActiveRecord', magicProperties: ['$isNewRecord']);
        $context->addMagicProperty('yii\\db\\ActiveRecord', '$isNewRecord', Type::bool());

        self::assertSame('mixed', (string) $this->attribute($context, 'app\\Record', 'isNewRecord'));
    }

    public function testCyclesAndUnknownAnnotationsTerminate(): void
    {
        $context = new AnalyzerContext();
        $this->hierarchy($context, 'app\\A', ['app\\B'], ['app\\Trait']);
        $context->addClass('app\\B', ['app\\A']);
        $this->hierarchy($context, 'app\\Trait', traits: ['app\\Trait']);

        self::assertNull(PropertyResolver::magic($context->codebase, 'app\\A', 'missing'));
    }

    public function testReceiverMetadataCanSupplyResolvedInheritedTemplate(): void
    {
        $context = new AnalyzerContext();
        $context->addClass('app\\Measurement', ['app\\TypedRecord']);
        $context->addClass('app\\TypedRecord', magicProperties: ['$value']);
        $template = Type::fromAtomic(
            new GenericParameterType(
                'T',
                Type::mixed(),
                new GenericParent(GenericParentKind::ClassLike, 'app\\TypedRecord'),
                null,
            ),
        );
        $context->addMagicProperty('app\\TypedRecord', '$value', $template);
        $context->addMagicProperty('app\\Measurement', '$value', Type::float());

        self::assertSame('float', (string) $this->attribute($context, 'app\\Measurement', 'value'));

        // Mago 1.57 currently supplies the unresolved template for @extends TypedRecord<float>.
        // Keep the SDK's type instead of guessing bindings unavailable in ClassLikeMetadata.
        $context->addMagicProperty('app\\Measurement', '$value', $template);
        self::assertSame('T', (string) $this->attribute($context, 'app\\Measurement', 'value'));
    }
}
