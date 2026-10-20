<?php

declare(strict_types=1);

namespace Corpus\BehaviorRegression;

use yii\base\Behavior;
use yii\base\Component;

use function Mago\confirm;

/** @method string forwardedLabel(int $id, string $prefix = 'label') */
class DocumentedBehavior extends Behavior
{
    public function forwardedNative(string $value): string
    {
        return $value;
    }
}

class InheritedBehavior extends DocumentedBehavior {}

class NativeParent extends Component
{
    public function nativeResult(int $value): int
    {
        return $value;
    }
}

class NativeOwner extends NativeParent
{
    public function directResult(int $value): int
    {
        return $value;
    }
}

/** @method int forwardedNative(string $value) */
class ForwardingOwner extends Component {}

class ConflictingBehavior extends Behavior
{
    public function directResult(string $value): string
    {
        return $value;
    }

    public function nativeResult(string $value): string
    {
        return $value;
    }
}

function acceptsString(string $value): void {}
function acceptsInt(int $value): void {}

function documentedMethods(ForwardingOwner $owner): void
{
    confirm($owner->forwardedLabel(1), 'string');
    confirm($owner->forwardedNative('value'), 'string');
    // @mago-expect analysis:invalid-argument
    $owner->forwardedNative(1);
    acceptsString($owner->forwardedLabel(prefix: 'tag', id: 2));
    // @mago-expect analysis:invalid-argument
    acceptsInt($owner->forwardedLabel(3));
    // @mago-expect analysis:invalid-argument
    $owner->forwardedLabel('wrong');
}

function inheritedNativeMethodWins(NativeOwner $owner): void
{
    confirm($owner->nativeResult(1), 'int');
    confirm($owner->directResult(1), 'int');
    // @mago-expect analysis:invalid-argument
    $owner->directResult('wrong');
    acceptsInt($owner->nativeResult(2));
    // @mago-expect analysis:invalid-argument
    acceptsString($owner->nativeResult(3));
    // @mago-expect analysis:invalid-argument
    $owner->nativeResult('wrong');
}
