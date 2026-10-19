<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\data\type;

use yii\base\Module;

use function PHPStan\Testing\assertType;

/**
 * Module reading its inherited `$params` property for configured params type assertions.
 */
final class ApplicationParamsModule extends Module
{
    public function testResolveOwnParams(): void
    {
        \Parity\assertType($this->params['tags'], \parity_expected_8205d0162dbaa46dcecd());
    }
}
