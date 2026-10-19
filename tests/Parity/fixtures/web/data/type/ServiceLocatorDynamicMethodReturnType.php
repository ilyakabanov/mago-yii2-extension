<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\di\ServiceLocator;
use yii\web\{Application, Request, Response, Session, User};
use yii2\extensions\phpstan\tests\support\stub\MyActiveRecord;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see ServiceLocator::get()} return types in PHPStan analysis.
 *
 * Verifies type inference for component resolution by ID across {@see ServiceLocator}, {@see Module}, and
 * {@see Application}, including named arguments, nullability driven by `$throwException`, and the `object` type declared
 * by Yii for class names that aren't component IDs and for unknown, non-constant, and union identifiers.
 */
final class ServiceLocatorDynamicMethodReturnType
{
    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnComponentClassWhenGetThrowExceptionIsUnpacked(): void
    {
        $locator = new ServiceLocator();

        \Parity\assertType($locator->get('user', ...[false]), \parity_expected_896b04840a23a9688541());
        \Parity\assertType($locator->get('user', ...[true]), \parity_expected_02ec97b8d36c75b7567d());
        \Parity\assertType(Yii::$app->get('view', ...[false]), \parity_expected_b46dcb731c3c05c28cc7());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnObjectWhenGetByBuiltInClassNames(): void
    {
        $locator = new ServiceLocator();

        \Parity\assertType($locator->get(User::class), \parity_expected_2958d416d08aa5a472d7());
        \Parity\assertType($locator->get(Request::class), \parity_expected_2958d416d08aa5a472d7());
        \Parity\assertType($locator->get(Response::class), \parity_expected_2958d416d08aa5a472d7());
        \Parity\assertType($locator->get(Session::class), \parity_expected_2958d416d08aa5a472d7());
        \Parity\assertType($locator->get(User::class, false), \parity_expected_d2d39c6ccbd08bab97f3());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnObjectWhenGetByClassNameOfContainerService(): void
    {
        $locator = new ServiceLocator();

        $className = 'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord';

        \Parity\assertType($locator->get(MyActiveRecord::class), \parity_expected_2958d416d08aa5a472d7());
        \Parity\assertType($locator->get($className), \parity_expected_2958d416d08aa5a472d7());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnObjectWhenGetIdIsUnpacked(): void
    {
        $locator = new ServiceLocator();

        \Parity\assertType($locator->get(...['user']), \parity_expected_2958d416d08aa5a472d7());
    }

    public function testReturnObjectWhenGetWithNonConstantId(string $id): void
    {
        $locator = new ServiceLocator();

        \Parity\assertType($locator->get($id), \parity_expected_2958d416d08aa5a472d7());
        \Parity\assertType($locator->get($id, false), \parity_expected_d2d39c6ccbd08bab97f3());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnObjectWhenGetWithUnionOfComponentIds(bool $useUser): void
    {
        $locator = new ServiceLocator();

        \Parity\assertType($locator->get($useUser ? 'user' : 'view'), \parity_expected_2958d416d08aa5a472d7());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnObjectWhenGetWithUnknownId(): void
    {
        $locator = new ServiceLocator();

        \Parity\assertType($locator->get('unknown-component'), \parity_expected_2958d416d08aa5a472d7());
        \Parity\assertType($locator->get('unknown-component', false), \parity_expected_d2d39c6ccbd08bab97f3());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnServiceWhenGetByComponentId(): void
    {
        $locator = new ServiceLocator();

        \Parity\assertType($locator->get('user'), \parity_expected_02ec97b8d36c75b7567d());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnServiceWhenGetByComponentIdWithThrowException(bool $throwException): void
    {
        $locator = new ServiceLocator();

        \Parity\assertType($locator->get('user'), \parity_expected_02ec97b8d36c75b7567d());
        \Parity\assertType($locator->get('user', true), \parity_expected_02ec97b8d36c75b7567d());
        \Parity\assertType($locator->get('user', false), \parity_expected_896b04840a23a9688541());
        \Parity\assertType($locator->get('user', $throwException), \parity_expected_896b04840a23a9688541());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnServiceWhenGetByNamedArguments(): void
    {
        $locator = new ServiceLocator();

        \Parity\assertType($locator->get(id: 'user'), \parity_expected_02ec97b8d36c75b7567d());
        \Parity\assertType($locator->get(id: 'user', throwException: true), \parity_expected_02ec97b8d36c75b7567d());
        \Parity\assertType($locator->get(id: 'user', throwException: false), \parity_expected_896b04840a23a9688541());
        \Parity\assertType($locator->get(throwException: false, id: 'user'), \parity_expected_896b04840a23a9688541());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnServiceWhenGetFromApplicationById(): void
    {
        $application = new Application();

        \Parity\assertType($application->get('user'), \parity_expected_02ec97b8d36c75b7567d());
        \Parity\assertType($application->get(User::class), \parity_expected_2958d416d08aa5a472d7());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnServiceWhenGetFromModuleById(): void
    {
        $module = new Module('test');

        \Parity\assertType($module->get('user'), \parity_expected_02ec97b8d36c75b7567d());
        \Parity\assertType($module->get(User::class), \parity_expected_2958d416d08aa5a472d7());
    }
}
