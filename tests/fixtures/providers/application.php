<?php

return [
    'mago' => ['application_type' => 'yii\\web\\Application'],
    'components' => [
        'cache' => ['class' => 'yii\\caching\\ArrayCache'],
        'user' => ['class' => 'yii\\web\\User', 'identityClass' => 'app\\User'],
    ],
];
