<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\console\data\property;

use Yii;
use yii\base\{Security, View};
use yii\console\{Application, ErrorHandler, Request, Response};
use yii\db\Connection;
use yii\i18n\{Formatter, I18N};
use yii\log\Dispatcher;
use yii\mail\MailerInterface;
use yii\web\{AssetManager, UrlManager};

use function PHPStan\Testing\{assertSuperType, assertType};

/**
 * Type assertion fixture for {@see Application} property reflection via `Yii::$app` in PHPStan analysis.
 *
 * Verifies type inference for core console application properties and components, including native, interface,
 * nullable, and union-typed members.
 */
final class ApplicationPropertiesClassReflectionType
{
    public function testReturnApplicationInstanceFromYiiApp(): void
    {
        \Parity\assertType(Yii::$app, \parity_expected_b8879daa361e4cf1ed23());
    }

    public function testReturnAssetManagerFromComponent(): void
    {
        \Parity\assertType(Yii::$app->assetManager, \parity_expected_6952b1d650bb55291b68());
    }

    public function testReturnAuthManagerFromComponent(): void
    {
        \Parity\assertType(Yii::$app->authManager, \parity_expected_bf57e3fc155499334459());
    }

    public function testReturnBoolFromEnableCoreCommandsProperty(): void
    {
        \Parity\assertType(Yii::$app->enableCoreCommands, \parity_expected_b760f44fa5965c2474a3());
    }

    public function testReturnCacheFromComponent(): void
    {
        \Parity\assertType(Yii::$app->cache, \parity_expected_bd655a9b7853d699887b());
    }

    public function testReturnControllerFromProperty(): void
    {
        // Yii 2.0.54 describes the generic controller as `Controller<yii\base\Module>`, later versions omit the default
        \Parity\assertSuperType(Yii::$app->controller, \parity_expected_aa657eb8b423a6c90585());
        \Parity\assertType(Yii::$app->controller === null, \parity_expected_b760f44fa5965c2474a3());
    }

    public function testReturnDbFromComponent(): void
    {
        \Parity\assertType(Yii::$app->db, \parity_expected_9eaececf3bd531cc4249());
    }

    public function testReturnErrorHandlerFromComponent(): void
    {
        \Parity\assertType(Yii::$app->errorHandler, \parity_expected_6ea8f5298f9d100508ca());
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
        \Parity\assertType(Yii::$app->request, \parity_expected_0bc3835f6b54ffb3b4f2());
    }

    public function testReturnResponseFromComponent(): void
    {
        \Parity\assertType(Yii::$app->response, \parity_expected_c4057ffd1effb13d8601());
    }

    public function testReturnSecurityFromComponent(): void
    {
        \Parity\assertType(Yii::$app->security, \parity_expected_65d0c924e44df4b8a898());
    }

    public function testReturnStringFromBasePathProperty(): void
    {
        \Parity\assertType(Yii::$app->basePath, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromCharsetProperty(): void
    {
        \Parity\assertType(Yii::$app->charset, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromControllerNamespaceProperty(): void
    {
        \Parity\assertType(Yii::$app->controllerNamespace, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromDefaultRouteProperty(): void
    {
        \Parity\assertType(Yii::$app->defaultRoute, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromLanguageProperty(): void
    {
        \Parity\assertType(Yii::$app->language, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromNameProperty(): void
    {
        \Parity\assertType(Yii::$app->name, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromRuntimePathProperty(): void
    {
        \Parity\assertType(Yii::$app->runtimePath, \parity_expected_473287f8298dba7163a8());
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

    public function testReturnStringFromVendorPathProperty(): void
    {
        \Parity\assertType(Yii::$app->vendorPath, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnUrlManagerFromComponent(): void
    {
        \Parity\assertType(Yii::$app->urlManager, \parity_expected_b8134bc05d0ed6d4f9be());
    }

    public function testReturnViewFromComponent(): void
    {
        \Parity\assertType(Yii::$app->view, \parity_expected_6ead6fbc114c2a19ed1d());
    }
}
