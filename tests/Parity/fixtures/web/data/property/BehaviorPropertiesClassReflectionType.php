<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\property;

use yii2\extensions\phpstan\tests\support\stub\MyComponent;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for behavior property reflection on {@see MyComponent} in PHPStan analysis.
 *
 * Verifies type inference for properties provided by attached behaviors, including parameterized, shared, virtual, and
 * native properties, as if they were declared on the component class.
 */
final class BehaviorPropertiesClassReflectionType
{
    public function testReturnArrayFromArrayProperty(): void
    {
        $component = new MyComponent();

        \Parity\assertType($component->arrayProperty, \parity_expected_f8836dfea71a673cc20c());
    }

    public function testReturnBooleanFromBooleanProperty(): void
    {
        $component = new MyComponent();

        \Parity\assertType($component->booleanProperty, \parity_expected_b760f44fa5965c2474a3());
    }

    public function testReturnIntegerFromBehaviorTwoProperty(): void
    {
        $component = new MyComponent();

        \Parity\assertType($component->behaviorTwoProperty, \parity_expected_6da88c34ba124c41f977());
    }

    public function testReturnMixedFromMixedProperty(): void
    {
        $component = new MyComponent();

        \Parity\assertType($component->mixedProperty, \parity_expected_3f8fee624f43b2a9d685());
    }

    public function testReturnStringFromBehaviorOneProperty(): void
    {
        $component = new MyComponent();

        \Parity\assertType($component->behaviorOneProperty, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromSharedProperty(): void
    {
        $component = new MyComponent();

        \Parity\assertType($component->sharedProperty, \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromVirtualProperty(): void
    {
        $component = new MyComponent();

        \Parity\assertType($component->virtualProperty, \parity_expected_473287f8298dba7163a8());
    }
}
