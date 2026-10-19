<?php

declare(strict_types=1);

namespace Corpus;

function configuredGlobalApplication(): void
{
    // Mago 1.57 only dispatches instance properties; Yii's static $app retains its nullable declaration.
    assert(\Yii::$app !== null);
    acceptsView(\Yii::$app->view);
    acceptsString(\Yii::$app->params['adminEmail']);
    // @mago-expect analysis:invalid-argument
    acceptsInt(\Yii::$app->params['adminEmail']);
}
