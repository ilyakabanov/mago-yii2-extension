<?php

declare(strict_types=1);

use Mago\Sdk\Worker;
use yii2\extensions\mago\Yii2Extension;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require __DIR__ . '/src/Models.php';

(new Worker(Yii2Extension::create(__DIR__ . '/config.php')))->run();
