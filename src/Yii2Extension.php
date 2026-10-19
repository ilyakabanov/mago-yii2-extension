<?php

declare(strict_types=1);

namespace yii2\extensions\mago;

use Mago\Sdk\Extension;
use yii2\extensions\mago\Analyzer\Yii2Plugin;

/** The single registration entrypoint for consuming projects. */
final class Yii2Extension
{
    /** @param array<string, string> $genericComponents */
    public static function create(
        string $configPath = '',
        array $genericComponents = ['user' => 'identityClass'],
    ): Extension {
        return new Extension(
            identifier: 'yii2/mago-extension',
            name: 'Yii2 support for Mago',
            version: '0.1.0',
            analyzerPlugins: [new Yii2Plugin(new ServiceMap($configPath), $genericComponents)],
        );
    }
}
