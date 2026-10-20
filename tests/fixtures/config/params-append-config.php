<?php

declare(strict_types=1);

use yii2\extensions\phpstan\tests\provider\ParamsTypeBuilderProvider;

require_once __DIR__ . '/../../reference/provider/ParamsTypeBuilderProvider.php';

$params = [];
foreach (ParamsTypeBuilderProvider::appendProvider() as $name => [$case]) {
    $params[$name] = $case;
}

return ['params' => $params];
