<?php

declare(strict_types=1);

function consumerExample(): string
{
    \Mago\confirm(Yii::$app->params['contactEmail'], 'string');
    \Mago\confirm(Yii::$app->view, 'yii\\web\\View');

    return Yii::$app->params['contactEmail'];
}
