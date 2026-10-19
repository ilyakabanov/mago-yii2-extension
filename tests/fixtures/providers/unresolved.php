<?php

return [
    'mago' => ['application_type' => 'yii\\web\\Application'],
    'components' => [
        'factory' => static fn () => new stdClass(),
        'cache' => ['keyPrefix' => 'test'],
    ],
];
