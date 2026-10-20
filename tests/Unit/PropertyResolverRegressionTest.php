<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use Mago\Sdk\Analyzer\Type;
use PHPUnit\Framework\TestCase;
use yii2\extensions\mago\Analyzer\PropertyResolver;
use yii2\extensions\mago\tests\Support\AnalyzerContext;

final class PropertyResolverRegressionTest extends TestCase
{
    public function testNativePropertyPrecedesMagicTagAndGetter(): void
    {
        $context = new AnalyzerContext();
        $context->addClass('app\\Record', magicProperties: ['$id'], methods: ['getId'], properties: ['$id']);
        $context->addProperty('app\\Record', '$id', Type::int());
        $context->addMagicProperty('app\\Record', '$id', Type::string());
        $context->addMethod('app\\Record', 'getId', Type::bool());

        $property = PropertyResolver::resolve($context->codebase, 'app\\Record', 'id');

        self::assertSame('int', (string) $property?->readType);
        self::assertSame('int', (string) $property?->writeType);
    }

    public function testDirectionalTagsDoNotFallThroughToGetter(): void
    {
        $context = new AnalyzerContext();
        $context->addClass(
            'app\\Record',
            magicProperties: ['$label', '$password'],
            methods: ['getLabel', 'getPassword'],
        );
        $context->addMagicProperty('app\\Record', '$label', Type::string(), readOnly: true);
        $context->addMagicProperty('app\\Record', '$password', Type::string(), writeOnly: true);
        $context->addMethod('app\\Record', 'getLabel', Type::bool());
        $context->addMethod('app\\Record', 'getPassword', Type::bool());

        $label = PropertyResolver::resolve($context->codebase, 'app\\Record', 'label');
        $password = PropertyResolver::resolve($context->codebase, 'app\\Record', 'password');

        self::assertSame('string', (string) $label?->readType);
        self::assertNull($label?->writeType);
        self::assertNotNull($password);
        self::assertNull($password->readType);
        self::assertSame('string', (string) $password->writeType);
    }

    public function testGetterIsFallbackOnlyWhenNoPropertyIsDeclared(): void
    {
        $context = new AnalyzerContext();
        $context->addClass('app\\Record', methods: ['getLabel']);
        $context->addMethod('app\\Record', 'getLabel', Type::string());

        $property = PropertyResolver::resolve($context->codebase, 'app\\Record', 'label');

        self::assertSame('string', (string) $property?->readType);
        self::assertNull($property?->writeType);
        self::assertNull(PropertyResolver::resolve($context->codebase, 'app\\Record', 'unknown'));
    }
}
