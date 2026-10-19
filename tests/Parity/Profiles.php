<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Parity;

final class Profiles
{
    public const CONFIGS = [
        'web' => 'phpstan-config.php',
        'header' => 'phpstan-config.php',
        'console' => 'phpstan-console-config.php',
        'custom' => 'phpstan-custom-app-config.php',
        'params' => 'phpstan-params-config.php',
        'awkward' => 'params-awkward-keys-config.php',
        'definition' => 'definition-forms-analysis-config.php',
        'self-test' => 'phpstan-config.php',
    ];
}
