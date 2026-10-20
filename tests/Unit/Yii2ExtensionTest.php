<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use Mago\Sdk\Analyzer\PluginRegistry;
use PHPUnit\Framework\TestCase;
use yii2\extensions\mago\Analyzer;
use yii2\extensions\mago\Yii2Extension;

final class Yii2ExtensionTest extends TestCase
{
    public function testFactoryExposesOnlyTheYii2AnalyzerPlugin(): void
    {
        $extension = Yii2Extension::create();

        self::assertSame('yii2/mago-extension', $extension->identifier);
        self::assertSame('Yii2 support for Mago', $extension->name);
        self::assertSame('0.1.0', $extension->version);
        self::assertCount(1, $extension->analyzerPlugins);
        self::assertInstanceOf(Analyzer\Yii2Plugin::class, $extension->analyzerPlugins[0]);
        self::assertSame([], $extension->linterRules);
        self::assertNull($extension->workerReducer);
    }

    public function testRegistersTheExpectedProvidersInOrder(): void
    {
        $registry = $this->registry();

        self::assertSame(
            [
                Analyzer\ContainerProvider::class,
                Analyzer\ServiceLocatorProvider::class,
                Analyzer\BehaviorMethodProvider::class,
                Analyzer\AttributeProvider::class,
                Analyzer\ActiveQueryProvider::class,
                Analyzer\HeaderProvider::class,
                Analyzer\FilterMessagesProvider::class,
            ],
            array_map(
                static fn(object $provider): string => $provider::class,
                $registry->getMethodReturnTypeProviders(),
            ),
        );
        self::assertSame(
            [
                Analyzer\ApplicationProvider::class,
                Analyzer\BehaviorProvider::class,
                Analyzer\LogMessagesProvider::class,
            ],
            array_map(static fn(object $provider): string => $provider::class, $registry->getPropertyTypeProviders()),
        );
    }

    public function testEnablesProviderMemoization(): void
    {
        self::assertTrue($this->registry()->shouldMemoizeProviders());
    }

    private function registry(): PluginRegistry
    {
        $registry = new PluginRegistry();
        Yii2Extension::create()->analyzerPlugins[0]->register($registry);

        return $registry;
    }
}
