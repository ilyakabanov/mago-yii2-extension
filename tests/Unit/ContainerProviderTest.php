<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use Mago\Sdk\Analyzer\Type;
use PHPUnit\Framework\TestCase;
use yii2\extensions\mago\Analyzer\ContainerProvider;
use yii2\extensions\mago\Analyzer\ServiceLocatorProvider;
use yii2\extensions\mago\ServiceMap;
use yii2\extensions\mago\tests\Support\AnalyzerContext;

final class ContainerProviderTest extends TestCase
{
    public function testContainerAndLocatorUseDifferentMaps(): void
    {
        $map = new ServiceMap(dirname(__DIR__) . '/corpus/config.php');
        $context = new AnalyzerContext();
        $container = new ContainerProvider($map);
        $locator = new ServiceLocatorProvider($map);
        $service = [AnalyzerContext::argument(Type::literalString('view-service'))];
        $component = [AnalyzerContext::argument(Type::literalString('view'))];
        self::assertSame(
            'yii\\web\\View',
            (string) $container->getReturnType($context->context(
                Type::namedObject('yii\\di\\Container'),
                'get',
                $service,
            )),
        );
        self::assertNull($container->getReturnType($context->context(
            Type::namedObject('yii\\di\\Container'),
            'get',
            $component,
        )));
        self::assertNull($locator->getReturnType($context->context(
            Type::namedObject('yii\\di\\ServiceLocator'),
            'get',
            $service,
        )));
        self::assertSame(
            'yii\\web\\View',
            (string) $locator->getReturnType($context->context(
                Type::namedObject('yii\\di\\ServiceLocator'),
                'get',
                $component,
            )),
        );
    }

    public function testUnresolvedFactoryAndNullableNamedLookup(): void
    {
        $provider = new ServiceLocatorProvider(new ServiceMap(dirname(__DIR__) . '/corpus/config.php'));
        $context = new AnalyzerContext();
        $arguments = [
            AnalyzerContext::argument(Type::false(), 'throwException'),
            AnalyzerContext::argument(Type::literalString('factory'), 'id'),
        ];
        self::assertSame(
            'object|null',
            (string) $provider->getReturnType($context->context(
                Type::namedObject('yii\\di\\ServiceLocator'),
                'get',
                $arguments,
            )),
        );
        self::assertNull($provider->getReturnType($context->context(
            Type::namedObject('yii\\di\\ServiceLocator'),
            'get',
            [AnalyzerContext::argument(Type::string())],
        )));
        self::assertNull($provider->getReturnType($context->context(
            Type::namedObject('yii\\di\\ServiceLocator'),
            'get',
            [AnalyzerContext::argument(Type::literalString('view'), unpacked: true)],
        )));
    }
}
