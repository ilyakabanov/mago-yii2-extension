<?php

declare(strict_types=1);

use Regression\BehaviorGenerics\IdentityBehavior;
use Regression\BehaviorGenerics\Owner;

return [
    'behaviors' => [
        Owner::class => [IdentityBehavior::class],
    ],
];
