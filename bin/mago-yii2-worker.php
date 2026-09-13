<?php

declare(strict_types=1);

use Ilyakabanov\MagoYii2\Yii2Extension;
use Mago\Sdk\Worker;

/** @var string|null $_composer_autoload_path */
require $_composer_autoload_path ?? dirname(__DIR__) . '/vendor/autoload.php';

(new Worker(Yii2Extension::create()))->run();
