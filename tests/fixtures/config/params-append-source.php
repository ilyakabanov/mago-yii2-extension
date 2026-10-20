<?php

declare(strict_types=1);

namespace ParamsAppendRegression;

use yii\base\Module;

use function Mago\confirm;

// These indices are PHP 8.3+ runtime results, not SDK array-item counts.
function nativeNegativeKeyControl(): void
{
    $params = [-5 => 42];
    $params[] = 43;
    confirm($params[-4], 'int(43)');
}

function negativeKey(Module $module): void
{
    $params = $module->params['negative key'];
    confirm($params[-5], 'string');
    $params[] = 42;
    confirm($params[-4], 'int(42)');
    confirm($params, 'array{-5: string, -4: int(42)}');
}

function negativeKeysOutOfOrder(Module $module): void
{
    $params = $module->params['negative keys out of order'];
    confirm($params[-2], 'string');
    confirm($params[-5], 'string');
    $params[] = 42;
    confirm($params[-1], 'int(42)');
    confirm($params, 'array{-5: string, -2: string, -1: int(42)}');
}

function negativeAndStringKeys(Module $module): void
{
    $params = $module->params['negative and string keys'];
    confirm($params['name'], 'string');
    confirm($params[-5], 'string');
    $params[] = 42;
    confirm($params[-4], 'int(42)');
    confirm($params, "array{-5: string, -4: int(42), 'name': string}");
}

function negativeAndZeroKeys(Module $module): void
{
    $params = $module->params['negative and zero keys'];
    confirm($params[-5], 'string');
    confirm($params[0], 'string');
    $params[] = 42;
    confirm($params[1], 'int(42)');
    confirm($params, 'array{-5: string, 0: string, 1: int(42)}');
}

function positiveSparseKeys(Module $module): void
{
    $params = $module->params['positive sparse keys'];
    confirm($params[2], 'string');
    confirm($params[5], 'string');
    $params[] = 42;
    confirm($params[6], 'int(42)');
    confirm($params, 'array{2: string, 5: string, 6: int(42)}');
}

function stringKeys(Module $module): void
{
    $params = $module->params['string keys'];
    confirm($params['name'], 'string');
    $params[] = 42;
    confirm($params[0], 'int(42)');
    confirm($params, "array{0: int(42), 'name': string}");
}
