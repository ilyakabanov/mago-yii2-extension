<?php

declare(strict_types=1);

$config = require __DIR__ . '/../../regressions/params-keys/config.php';
$config['params'] += [
    "both'\"quotes" => 1.5,
    'ending\\' => ['both\'\"' => [], 'list' => ['a', 'b']],
    '09' => true,
    '-09' => 1,
    -1 => null,
    "*/'; throw new \\RuntimeException('injected'); //" => false,
];

return $config;
