<?php

declare(strict_types=1);

use Ilyakabanov\MagoYii2\Yii2Extension;
use Mago\Sdk\Worker;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

(new Worker(Yii2Extension::create()))->run();
