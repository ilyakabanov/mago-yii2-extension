<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\data\type;

use Yii;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for params whose keys need quoting or escaping when written as a PHPDoc array shape.
 */
final class ParamsAwkwardKeysType
{
    public function testResolveEveryKey(): void
    {
        \Parity\assertType(Yii::$app->params["it's"], \parity_expected_473287f8298dba7163a8());
        \Parity\assertType(Yii::$app->params['back\\slash'], \parity_expected_473287f8298dba7163a8());
        \Parity\assertType(Yii::$app->params['quote"d'], \parity_expected_6da88c34ba124c41f977());
        \Parity\assertType(Yii::$app->params[''], \parity_expected_473287f8298dba7163a8());
        \Parity\assertType(Yii::$app->params[' '], \parity_expected_76a7e234dc54e9c605b2());
        \Parity\assertType(Yii::$app->params['a b'], \parity_expected_b760f44fa5965c2474a3());
        \Parity\assertType(Yii::$app->params["new\nline"], \parity_expected_6da88c34ba124c41f977());
        \Parity\assertType(Yii::$app->params["tab\tkey"], \parity_expected_74234e98afe7498fb5da());
        \Parity\assertType(Yii::$app->params['x*/y'], \parity_expected_6da88c34ba124c41f977());
        \Parity\assertType(Yii::$app->params['a$b'], \parity_expected_473287f8298dba7163a8());
        \Parity\assertType(Yii::$app->params["c\x01"], \parity_expected_6da88c34ba124c41f977());
        \Parity\assertType(Yii::$app->params['é'], \parity_expected_473287f8298dba7163a8());
        \Parity\assertType(Yii::$app->params[-5], \parity_expected_473287f8298dba7163a8());
        \Parity\assertType(Yii::$app->params[123], \parity_expected_473287f8298dba7163a8());
        \Parity\assertType(Yii::$app->params['nested']['*/'], \parity_expected_6da88c34ba124c41f977());
        \Parity\assertType(Yii::$app->params['nested']["line\nbreak"], \parity_expected_57fca985eac562366e40());
        \Parity\assertType(Yii::$app->params['list'], \parity_expected_8205d0162dbaa46dcecd());
    }
}
