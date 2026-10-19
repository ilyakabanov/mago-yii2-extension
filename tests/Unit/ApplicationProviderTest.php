<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use Mago\Sdk\Analyzer\Type;
use PHPUnit\Framework\TestCase;
use yii2\extensions\mago\Analyzer\ApplicationProvider;
use yii2\extensions\mago\ServiceMap;
use yii2\extensions\mago\tests\Support\AnalyzerContext;

final class ApplicationProviderTest extends TestCase
{
    public function testApplicationTypeAndConfiguredComponent(): void
    {
        $map = new ServiceMap(__DIR__ . '/../fixtures/providers/application.php');
        $provider = new ApplicationProvider($map);
        $context = new AnalyzerContext();
        $context->addClass('yii\\BaseYii');
        $context->addClass('yii\\web\\Application', ['yii\\base\\Application']);

        self::assertSame(
            'yii\\web\\Application',
            (string) $provider->getPropertyType($context->propertyContext('yii\\BaseYii', 'app'))?->readType,
        );
        self::assertSame(
            'yii\\caching\\ArrayCache',
            (string) $provider->getPropertyType($context->propertyContext('yii\\web\\Application', 'cache'))?->readType,
        );
        self::assertSame(
            'yii\\web\\User<app\\User>',
            (string) $provider->getPropertyType($context->propertyContext('yii\\web\\Application', 'user'))?->readType,
        );
    }

    public function testUnknownFactoryOverridesAnnotationButClasslessCoreComponentKeepsIt(): void
    {
        $map = new ServiceMap(__DIR__ . '/../fixtures/providers/unresolved.php');
        $provider = new ApplicationProvider($map);
        $context = new AnalyzerContext();
        $context->addClass('yii\\web\\Application', ['yii\\base\\Application'], ['factory', 'cache']);
        $context->addClass('yii\\base\\Application');
        $context->addMagicProperty('yii\\web\\Application', 'factory', Type::string());
        $context->addMagicProperty('yii\\web\\Application', 'cache', Type::namedObject('yii\\caching\\Cache'));

        self::assertSame(
            'object',
            (string) $provider->getPropertyType($context->propertyContext(
                'yii\\web\\Application',
                'factory',
            ))?->readType,
        );
        self::assertSame(
            'yii\\caching\\Cache',
            (string) $provider->getPropertyType($context->propertyContext('yii\\web\\Application', 'cache'))?->readType,
        );
        self::assertNull($provider->getPropertyType($context->propertyContext('yii\\web\\Application', 'missing')));
    }
}
