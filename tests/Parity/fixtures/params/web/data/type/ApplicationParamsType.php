<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\data\type;

use Countable;
use Yii;
use yii\base\{Application as BaseApplication, Module};
use yii\console\Application as ConsoleApplication;
use yii\web\Application as WebApplication;
use yii2\extensions\phpstan\tests\support\stub\ApplicationParamsOwner;

use function is_int;
use function is_string;
use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for the stub-declared `Yii::$app` and `Module::$params` types, which PHPStan narrows,
 * assigns and merges natively.
 */
final class ApplicationParamsType
{
    public function testAssignedApplication(ConsoleApplication $console): void
    {
        Yii::$app = $console;

        \Parity\assertType(Yii::$app, \parity_expected_b8879daa361e4cf1ed23());
    }

    public function testAssignedBaseApplication(BaseApplication $base): void
    {
        Yii::$app = $base;

        \Parity\assertType(Yii::$app, \parity_expected_df8e53580cf664e137cc());
    }

    public function testAssignedNullableApplication(WebApplication|null $web): void
    {
        Yii::$app = $web;

        \Parity\assertType(Yii::$app, \parity_expected_c204250b27d62827cba4());
    }

    public function testAssignedParam(): void
    {
        Yii::$app->params['adminEmail'] = 1;

        \Parity\assertType(Yii::$app->params['adminEmail'], \parity_expected_6b86b273ff34fce19d6b());
        \Parity\assertType(Yii::$app->params['maxItems'], \parity_expected_6da88c34ba124c41f977());
    }

    /**
     * @param list<string> $values
     */
    public function testAssignedParamInForeach(array $values): void
    {
        foreach ($values as $value) {
            Yii::$app->params['maxItems'] = $value;
        }

        \Parity\assertType(Yii::$app->params['maxItems'], \parity_expected_6da88c34ba124c41f977());
    }

    public function testAssignedParamInIfElseBranches(bool $condition): void
    {
        if ($condition) {
            Yii::$app->params['maxItems'] = 'x';
        } else {
            Yii::$app->params['maxItems'] = 5;
        }

        \Parity\assertType(Yii::$app->params['maxItems'], \parity_expected_f0b6216851eaf700b515());
    }

    public function testAssignedUnionApplication(ConsoleApplication $console, WebApplication $web, bool $condition): void
    {
        Yii::$app = $condition ? $console : $web;

        \Parity\assertType(Yii::$app, \parity_expected_5c88f3f32930f1bdeea3());

        if (Yii::$app instanceof ConsoleApplication) {
            \Parity\assertType(Yii::$app, \parity_expected_b8879daa361e4cf1ed23());
        }
    }

    public function testContradictoryNarrowingIsNever(): void
    {
        if (is_string(Yii::$app->params['adminEmail']) && is_int(Yii::$app->params['adminEmail'])) {
            \Parity\assertType(Yii::$app->params['adminEmail'], \parity_expected_6497e4b3d7bed16979a3());
        }
    }

    public function testIssetAndNullCoalesce(): void
    {
        $email = Yii::$app->params['adminEmail'] ?? 'default';

        \Parity\assertType($email, \parity_expected_473287f8298dba7163a8());
        \Parity\assertType(Yii::$app->params['nested']['key1'] ?? 'default', \parity_expected_473287f8298dba7163a8());
        \Parity\assertType(Yii::$app->params['nullableParam'] ?? 'default', \parity_expected_4332f2ead9dfcb351eb9());
        \Parity\assertType(Yii::$app->params['missing'] ?? 'default', \parity_expected_4332f2ead9dfcb351eb9());
        \Parity\assertType(isset(Yii::$app->params['adminEmail']) ? Yii::$app->params['debugMode'] : false, \parity_expected_b760f44fa5965c2474a3());
        \Parity\assertType(Yii::$app->params['nested']['key2'], \parity_expected_6da88c34ba124c41f977());

        if (isset(Yii::$app->params['adminEmail'])) {
            \Parity\assertType(Yii::$app->params['adminEmail'], \parity_expected_473287f8298dba7163a8());
        }
    }

    public function testNarrowedApplication(): void
    {
        if (Yii::$app instanceof ConsoleApplication) {
            \Parity\assertType(Yii::$app, \parity_expected_6497e4b3d7bed16979a3());
        }
    }

    public function testNarrowedParams(): void
    {
        if (Yii::$app->params['adminEmail'] === 'admin@example.com') {
            \Parity\assertType(Yii::$app->params['adminEmail'], \parity_expected_8e087c2fa661fb0d0caf());
        }

        if (is_string(Yii::$app->params['nested']['key1'])) {
            \Parity\assertType(Yii::$app->params['nested']['key1'], \parity_expected_473287f8298dba7163a8());
        }

        \Parity\assertType(Yii::$app->params['nullableParam'], \parity_expected_74234e98afe7498fb5da());
    }

    public function testPushedListParam(): void
    {
        Yii::$app->params['tags'][] = 'z';

        \Parity\assertType(Yii::$app->params['tags'], \parity_expected_299b50a9473a1d9c3db5());
    }

    public function testResolveApplicationThroughAnyBaseYiiSpelling(): void
    {
        \Parity\assertType(\yii\BaseYii::$app, \parity_expected_ae1089551aeab8a15208());
        \Parity\assertType(\Yii::$app, \parity_expected_ae1089551aeab8a15208());
    }

    public function testResolveParamsThroughApplicationVariable(): void
    {
        $app = Yii::$app;

        \Parity\assertType($app->params['adminEmail'], \parity_expected_473287f8298dba7163a8());
    }

    public function testResolveParamsThroughIntersectionAndUnionOwners(
        Module&Countable $countable,
        Module|ApplicationParamsOwner $owner,
    ): void {
        \Parity\assertType($countable->params['maxItems'], \parity_expected_6da88c34ba124c41f977());
        \Parity\assertType($owner->params['maxItems'], \parity_expected_a69c69291a42ebaa09d8());
    }

    public function testResolveParamsThroughNullsafeModule(): void
    {
        \Parity\assertType(Yii::$app->getModule('admin')?->params, \parity_expected_c4b68f56aa3ccec5dab9());
    }
}
