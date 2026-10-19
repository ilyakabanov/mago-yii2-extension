<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\method;

use yii2\extensions\phpstan\tests\support\stub\MyComponent;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for behavior method reflection on {@see MyComponent} in PHPStan analysis.
 *
 * Verifies type inference for methods provided by attached behaviors, including parameterized, shared, and native
 * methods, as if they were declared on the component class.
 */
final class BehaviorMethodsClassReflectionType
{
    public function testReturnIntegerFromMethodWithReturnType(): void
    {
        $component = new MyComponent();

        \Parity\assertType($component->methodWithReturnType(), \parity_expected_6da88c34ba124c41f977());
    }

    public function testReturnStringFromBehaviorOneMethod(): void
    {
        $component = new MyComponent();

        \Parity\assertType($component->behaviorOneMethod(), \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromBehaviorTwoMethod(): void
    {
        $component = new MyComponent();

        \Parity\assertType($component->behaviorTwoMethod(), \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromMethodWithParameters(): void
    {
        $component = new MyComponent();

        \Parity\assertType($component->methodWithParameters('test', 123), \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringFromSharedMethod(): void
    {
        $component = new MyComponent();

        \Parity\assertType($component->sharedMethod(), \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnTypeInferenceForMethodWithoutExplicitReturnType(): void
    {
        $component = new MyComponent();

        \Parity\assertType($component->methodWithoutReturnType(), \parity_expected_473287f8298dba7163a8());
    }
}
