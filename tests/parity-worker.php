<?php

declare(strict_types=1);

use Mago\Sdk\Extension;
use Mago\Sdk\Worker;
use yii2\extensions\mago\Analyzer\Yii2Plugin;
use yii2\extensions\mago\ServiceMap;
use yii2\extensions\mago\tests\Parity\ParityPlugin;
use yii2\extensions\mago\tests\Parity\Profiles;

require dirname(__DIR__) . '/vendor/autoload.php';

require __DIR__ . '/Parity/reference-autoload.php';
$profiles = Profiles::CONFIGS;
$profile = getenv('YII2_PARITY_PROFILE') ?: 'web';
if (!isset($profiles[$profile])) {
    throw new RuntimeException('Unknown parity profile: ' . $profile);
}

(new Worker(new Extension(
    identifier: 'yii2/mago-extension-parity',
    name: 'Yii2 original assertion parity',
    version: '0.1.0',
    analyzerPlugins: [new Yii2Plugin(new ServiceMap(__DIR__ . '/reference/config/' . $profiles[$profile])), new ParityPlugin()],
)))->run();
