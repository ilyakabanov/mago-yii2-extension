<?php

declare(strict_types=1);

return [
    'mago' => ['application_type' => yii\web\Application::class],
    'params' => [
        'ordinary' => 'value',
        'x*/y' => 1,
        "new\nline" => 2,
        'new\nline' => false,
        "null\0key" => true,
        "it's" => 1.5,
        'quote"d' => null,
        'back\\slash' => 'value',
        "tab\tkey" => null,
        "control\x01\x07\x08\x1f\x7f" => 1,
        'é' => 'value',
        '' => 'empty',
        -5 => false,
        '123' => 1.5,
        'nested' => ['*/' => ["line\nbreak" => ['ok' => false]], 'ordinary' => 1],
        'list' => ['a', 'b'],
    ],
];
