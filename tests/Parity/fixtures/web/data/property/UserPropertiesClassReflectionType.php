<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\property;

use Yii;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see \yii\web\User} component property reflection via `Yii::$app->user` in PHPStan
 * analysis.
 *
 * Verifies type inference for user component properties, including identity, guest, and nullable identity members
 * accessed through the null-safe operator.
 */
final class UserPropertiesClassReflectionType
{
    public function testReturnBooleanFromIsGuestProperty(): void
    {
        \Parity\assertType(Yii::$app->user->isGuest, \parity_expected_b760f44fa5965c2474a3());
    }

    public function testReturnBooleanOrNullFromValidateAuthKeyMethod(): void
    {
        \Parity\assertType(Yii::$app->user->identity?->validateAuthKey('123abc'), \parity_expected_db218be0b69c38479862());
    }

    public function testReturnIdentityFromIdentityProperty(): void
    {
        \Parity\assertType(Yii::$app->user->identity, \parity_expected_49c1fd1330b7ca3211b1());
    }

    public function testReturnStringFromEmailProperty(): void
    {
        \Parity\assertType(Yii::$app->user->identity?->email, \parity_expected_25a45dc86aae4028edf8());
    }

    public function testReturnStringFromNameProperty(): void
    {
        \Parity\assertType(Yii::$app->user->identity?->name, \parity_expected_25a45dc86aae4028edf8());
    }

    public function testReturnStringFromReturnUrlProperty(): void
    {
        \Parity\assertType(Yii::$app->user->returnUrl, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringOrNullFromGetAuthKeyMethod(): void
    {
        \Parity\assertType(Yii::$app->user->identity?->getAuthKey(), \parity_expected_25a45dc86aae4028edf8());
    }

    public function testReturnUnionFromGetIdMethod(): void
    {
        \Parity\assertType(Yii::$app->user->identity?->getId(), \parity_expected_586cf00f65235bf62aa9());
    }

    public function testReturnUnionFromIdProperty(): void
    {
        \Parity\assertType(Yii::$app->user->identity?->id, \parity_expected_586cf00f65235bf62aa9());
    }
}
