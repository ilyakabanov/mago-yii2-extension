<?php

declare(strict_types=1);

namespace yii2\extensions\mago\Analyzer;

use Mago\Sdk\Analyzer\Plugin;
use Mago\Sdk\Analyzer\PluginDefinition;
use Mago\Sdk\Analyzer\PluginRegistry;
use yii2\extensions\mago\ServiceMap;

final class Yii2Plugin implements Plugin
{
    /** @param array<string, string> $genericComponents */
    public function __construct(
        private readonly ServiceMap $map,
        private readonly array $genericComponents = ['user' => 'identityClass'],
    ) {}

    public function getDefinition(): PluginDefinition
    {
        return new PluginDefinition(
            'yii2/framework',
            'Yii2 framework',
            'Configuration-aware Yii2 type inference.',
            aliases: ['yii2'],
        );
    }

    public function register(PluginRegistry $registry): void
    {
        // Providers are deterministic within one frozen analysis generation.
        $registry->enableProviderMemoization();

        $registry->registerPropertyTypeProvider(new ApplicationProvider($this->map, $this->genericComponents));
        $registry->registerPropertyTypeProvider(new BehaviorProvider($this->map));
        $registry->registerPropertyTypeProvider(new LogMessagesProvider());
        $registry->registerMethodReturnTypeProvider(new ContainerProvider($this->map));
        $registry->registerMethodReturnTypeProvider(new ServiceLocatorProvider($this->map));
        $registry->registerMethodReturnTypeProvider(new BehaviorMethodProvider($this->map));
        $registry->registerMethodReturnTypeProvider(new AttributeProvider($this->map));
        $registry->registerMethodReturnTypeProvider(new ActiveQueryProvider());
        $registry->registerMethodReturnTypeProvider(new HeaderProvider());
        $registry->registerMethodReturnTypeProvider(new FilterMessagesProvider());
    }
}
