<?php

declare(strict_types=1);

namespace Ilyakabanov\MagoYii2\Analyzer;

use Mago\Sdk\Analyzer\Plugin;
use Mago\Sdk\Analyzer\PluginDefinition;
use Mago\Sdk\Analyzer\PluginRegistry;

/**
 * Registers Yii2-specific analyzer capabilities.
 *
 * @internal
 */
final class Yii2Plugin implements Plugin
{
    public function getDefinition(): PluginDefinition
    {
        return new PluginDefinition(
            identifier: 'yii2/framework',
            name: 'Yii2 Framework',
            description: 'Understands Yii2 framework conventions.',
        );
    }

    public function register(PluginRegistry $registry): void
    {
        // Yii2-specific providers and hooks will be registered here.
    }
}
