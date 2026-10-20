<?php

declare(strict_types=1);

namespace StaticCorpus;

use yii\web\Application;
use yii\web\IdentityInterface;

use function Mago\confirm;

/** @extends Application<IdentityInterface> */
final class AssignedWebApplication extends Application {}

/** @extends Application<IdentityInterface> */
final class OtherWebApplication extends Application {}

function compatibleApplicationAssignment(AssignedWebApplication $app): void
{
    \Yii::$app = $app;

    confirm(\Yii::$app, 'StaticCorpus\\AssignedWebApplication');
}

function compatibleBaseYiiAssignment(AssignedWebApplication $app): void
{
    \yii\BaseYii::$app = $app;

    confirm(\yii\BaseYii::$app, 'StaticCorpus\\AssignedWebApplication');
}

function compatibleApplicationUnion(AssignedWebApplication $first, OtherWebApplication $second, bool $condition): void
{
    \Yii::$app = $condition ? $first : $second;

    confirm(\Yii::$app, 'StaticCorpus\\AssignedWebApplication|StaticCorpus\\OtherWebApplication');

    if (\Yii::$app instanceof AssignedWebApplication) {
        confirm(\Yii::$app, 'StaticCorpus\\AssignedWebApplication');
    } else {
        confirm(\Yii::$app, 'StaticCorpus\\OtherWebApplication');
    }
}

function applicationAssignmentDoesNotWidenInitialType(): void
{
    confirm(\Yii::$app, 'yii\\web\\Application<yii\\web\\IdentityInterface>');
    confirm(\yii\BaseYii::$app, 'yii\\web\\Application<yii\\web\\IdentityInterface>');
}
