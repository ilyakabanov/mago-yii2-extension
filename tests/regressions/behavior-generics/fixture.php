<?php

declare(strict_types=1);

namespace Regression\BehaviorGenerics;

use yii\base\Behavior;
use yii\base\Component;

use function Mago\confirm;

final class Owner extends Component {}

final class IdentityBehavior extends Behavior
{
    /**
     * @template T
     * @param T $value
     * @return T
     */
    public function identity(mixed $value): mixed
    {
        return $value;
    }
}

function compare(Owner $owner, IdentityBehavior $behavior, string $value): void
{
    confirm($behavior->identity($value), 'string');
    confirm($owner->identity($value), 'string');
}
