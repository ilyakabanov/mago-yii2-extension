<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\custom\data\property;

use Yii;
use yii2\extensions\phpstan\tests\support\stub\ApplicationCustom;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see ApplicationCustom} property reflection via `Yii::$app` in PHPStan analysis.
 *
 * Verifies that `Yii::$app` resolves to the configured custom application type when a user-defined application class is
 * specified in the PHPStan configuration, including virtual properties.
 */
final class ApplicationPropertiesClassReflectionType
{
    public function testReturnCustomApplicationInstanceFromYiiApp(): void
    {
        \Parity\assertType(Yii::$app, \parity_expected_6f9bbe47ef0e914f5097());
    }

    public function testReturnStringFromCharsetProperty(): void
    {
        \Parity\assertType(Yii::$app->charset, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromDefaultRouteProperty(): void
    {
        \Parity\assertType(Yii::$app->defaultRoute, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromLanguageProperty(): void
    {
        \Parity\assertType(Yii::$app->language, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromVirtualProperty(): void
    {
        \Parity\assertType(Yii::$app->virtualProperty, \parity_expected_473287f8298dba7163a8());
    }
}
