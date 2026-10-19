<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\data\property;

use Yii;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for `Yii::$app->params` type inference in PHPStan analysis.
 *
 * Validates that PHPStan correctly infers array shape types for application params when configured via the extension
 * configuration file.
 */
final class ApplicationParamsType
{
    public function testReturnArrayShapeFromParams(): void
    {
        \Parity\assertType(Yii::$app->params, \parity_expected_d9a201a9ebe12a73ded6());
    }

    public function testReturnBoolFromParamsKey(): void
    {
        \Parity\assertType(Yii::$app->params['debugMode'], \parity_expected_b760f44fa5965c2474a3());
    }

    public function testReturnFloatFromParamsKey(): void
    {
        \Parity\assertType(Yii::$app->params['ratio'], \parity_expected_76a7e234dc54e9c605b2());
    }

    public function testReturnIntFromParamsKey(): void
    {
        \Parity\assertType(Yii::$app->params['maxItems'], \parity_expected_6da88c34ba124c41f977());
    }

    public function testReturnNestedArrayShapeFromParamsKey(): void
    {
        \Parity\assertType(Yii::$app->params['nested'], \parity_expected_4dc8a72226ae121a226b());
    }

    public function testReturnNullFromParamsKey(): void
    {
        \Parity\assertType(Yii::$app->params['nullableParam'], \parity_expected_74234e98afe7498fb5da());
    }

    public function testReturnStringFromDottedParamsKey(): void
    {
        \Parity\assertType(Yii::$app->params['turnstile.siteKey'], \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromParamsKey(): void
    {
        \Parity\assertType(Yii::$app->params['adminEmail'], \parity_expected_473287f8298dba7163a8());
    }
}
