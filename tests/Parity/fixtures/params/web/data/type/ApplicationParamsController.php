<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\data\type;

use yii\base\Module;
use yii\web\Controller;

use function PHPStan\Testing\assertType;

/**
 * Controller reading `$this->module->params` for configured params type assertions.
 *
 * @extends Controller<Module>
 */
final class ApplicationParamsController extends Controller
{
    public function testResolveModuleParams(): void
    {
        \Parity\assertType($this->module->params['maxItems'], \parity_expected_6da88c34ba124c41f977());
    }
}
