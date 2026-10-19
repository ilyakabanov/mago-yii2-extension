<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\data\property;

use Yii;
use yii\db\Connection;
use yii\i18n\{Formatter, I18N};
use yii\log\Dispatcher;
use yii\mail\MailerInterface;
use yii\web\{
    Application,
    AssetManager,
    ErrorHandler,
    Request,
    Response,
    Session,
    UrlManager,
    View,
};

use function PHPStan\Testing\{assertSuperType, assertType};

/**
 * Type assertion fixture for {@see Application} property reflection via `Yii::$app` in PHPStan analysis.
 *
 * Verifies type inference for core web application properties and components, including native, interface, nullable,
 * and union-typed members.
 */
final class ApplicationPropertiesClassReflectionType
{
    public function testReturnApplicationInstanceFromYiiApp(): void
    {
        \Parity\assertType(Yii::$app, \parity_expected_ae1089551aeab8a15208());
    }

    public function testReturnAssetManagerFromComponent(): void
    {
        \Parity\assertType(Yii::$app->assetManager, \parity_expected_6952b1d650bb55291b68());
    }

    public function testReturnCacheFromComponent(): void
    {
        \Parity\assertType(Yii::$app->cache, \parity_expected_bd655a9b7853d699887b());
    }

    public function testReturnControllerFromProperty(): void
    {
        // Yii 2.0.54 describes the generic controller as `Controller<yii\base\Module>`, later versions omit the default
        \Parity\assertSuperType(Yii::$app->controller, \parity_expected_d252c41b0a55d4dd277d());
        \Parity\assertType(Yii::$app->controller === null, \parity_expected_b760f44fa5965c2474a3());
    }

    public function testReturnDbFromComponent(): void
    {
        \Parity\assertType(Yii::$app->db, \parity_expected_9eaececf3bd531cc4249());
    }

    public function testReturnErrorHandlerFromComponent(): void
    {
        \Parity\assertType(Yii::$app->errorHandler, \parity_expected_cdc95e754b15224c705d());
    }

    public function testReturnFormatterFromComponent(): void
    {
        \Parity\assertType(Yii::$app->formatter, \parity_expected_b2b967951afb39b0b435());
    }

    public function testReturnI18nFromComponent(): void
    {
        \Parity\assertType(Yii::$app->i18n, \parity_expected_2746c63d83b17da22e30());
    }

    public function testReturnLogFromComponent(): void
    {
        \Parity\assertType(Yii::$app->log, \parity_expected_1c5be1ebf01c1f8e1ad2());
    }

    public function testReturnMailerFromComponent(): void
    {
        \Parity\assertType(Yii::$app->mailer, \parity_expected_67693afd5fa2ba088af1());
    }

    public function testReturnRequestFromComponent(): void
    {
        \Parity\assertType(Yii::$app->request, \parity_expected_8be172a009e0cda557cf());
    }

    public function testReturnResponseFromComponent(): void
    {
        \Parity\assertType(Yii::$app->response, \parity_expected_4d2cdb0454e75b58b46f());
    }

    public function testReturnSessionFromComponent(): void
    {
        \Parity\assertType(Yii::$app->session, \parity_expected_fb41c589054f695781da());
    }

    public function testReturnStringFromCharsetProperty(): void
    {
        \Parity\assertType(Yii::$app->charset, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromDefaultRouteProperty(): void
    {
        \Parity\assertType(Yii::$app->defaultRoute, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromHomeUrlProperty(): void
    {
        \Parity\assertType(Yii::$app->homeUrl, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromLanguageProperty(): void
    {
        \Parity\assertType(Yii::$app->language, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromSourceLanguageProperty(): void
    {
        \Parity\assertType(Yii::$app->sourceLanguage, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromTimeZoneProperty(): void
    {
        \Parity\assertType(Yii::$app->timeZone, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromUniqueIdProperty(): void
    {
        \Parity\assertType(Yii::$app->uniqueId, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnUrlManagerFromComponent(): void
    {
        \Parity\assertType(Yii::$app->urlManager, \parity_expected_b8134bc05d0ed6d4f9be());
    }

    public function testReturnUserFromComponent(): void
    {
        \Parity\assertType(Yii::$app->user, \parity_expected_fbeebc4e2cb207054cfb());
    }

    public function testReturnViewFromComponent(): void
    {
        \Parity\assertType(Yii::$app->view, \parity_expected_b2fa295eac0bd39085c3());
    }
}
