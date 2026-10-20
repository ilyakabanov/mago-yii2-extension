<?php

declare(strict_types=1);

return [
    'mago' => ['application_type' => yii\web\Application::class],
    'params' => [
        'ordinary' => 'value',
        "it's" => 1.5,
        'quote"d' => 1,
        'back\\slash' => 'value',
        'new\\nline' => false,
        'nested' => ["it's" => false, 'back\\slash' => 1],
    ],
];
