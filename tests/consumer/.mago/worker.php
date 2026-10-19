<?php

declare(strict_types=1);

use Mago\Sdk\Worker;
use yii2\extensions\mago\Yii2Extension;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Worker(Yii2Extension::create(dirname(__DIR__) . '/config/analysis.php')))->run();
