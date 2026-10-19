<?php

declare(strict_types=1);

namespace StaticCorpus;

use function Mago\confirm;

function configuredStaticApplication(): void
{
    confirm(\Yii::$app, 'yii\\web\\Application<yii\\web\\IdentityInterface>');
    confirm(\Yii::$app->view, 'yii\\web\\View');
    confirm(\Yii::$app->params['adminEmail'], 'string');
    confirm(\Yii::$app->params['nested']['enabled'], 'bool');
    confirm(\yii\BaseYii::$app, 'yii\\web\\Application<yii\\web\\IdentityInterface>');
    confirm(\yii\BaseYii::$app->params['adminEmail'], 'string');
    confirm(\Yii::getVersion(), 'string');
    confirm(\yii\BaseYii::getVersion(), 'string');
    confirm(\Yii::createObject(\yii\web\View::class), 'yii\\web\\View');
    confirm(\yii\BaseYii::createObject(\yii\web\View::class), 'yii\\web\\View');
}
