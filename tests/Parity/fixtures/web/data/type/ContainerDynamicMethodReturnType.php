<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use Exception;
use yii\base\InvalidConfigException;
use yii\di\{Container, NotInstantiableException};
use yii2\extensions\phpstan\tests\support\stub\MyActiveRecord;

use function PHPStan\Testing\assertType;
use function random_int;

/**
 * Type assertion fixture for {@see Container::get()} return types in PHPStan analysis.
 *
 * Verifies type inference for container lookups, covering class-string and string identifiers, service definitions,
 * closures, singletons, parameterized instantiation, named arguments, the `object` type declared by Yii for unknown,
 * non-constant, and union identifiers, and `object` for one-element definition lists, which Yii rejects.
 */
final class ContainerDynamicMethodReturnType
{
    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnClassWhenGetByClassName(): void
    {
        $container = new Container();

        $activeRecord = $container->get(MyActiveRecord::class);

        \Parity\assertType($activeRecord, \parity_expected_a9c90bb1f4c06e6f7920());
        \Parity\assertType($activeRecord->flag, \parity_expected_b760f44fa5965c2474a3());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnClassWhenGetByClassNameString(): void
    {
        $container = new Container();

        $className = 'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord';

        \Parity\assertType($container->get($className), \parity_expected_a9c90bb1f4c06e6f7920());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnClassWhenGetByNamedClassArgument(): void
    {
        $container = new Container();

        \Parity\assertType($container->get(class: MyActiveRecord::class), \parity_expected_a9c90bb1f4c06e6f7920());
        \Parity\assertType($container->get(params: [], class: Exception::class), \parity_expected_b4fe3d529dee467ddcf2());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnObjectWhenGetIdIsUnpacked(): void
    {
        $container = new Container();

        \Parity\assertType($container->get(...['service']), \parity_expected_2958d416d08aa5a472d7());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnObjectWhenGetNestedService(): void
    {
        $container = new Container();

        \Parity\assertType($container->get('nested-service-class'), \parity_expected_2958d416d08aa5a472d7());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnObjectWhenGetNestedSingleton(): void
    {
        $container = new Container();

        \Parity\assertType($container->get('singleton-nested-service-class'), \parity_expected_2958d416d08aa5a472d7());
    }

    public function testReturnObjectWhenGetWithNonConstantId(string $id): void
    {
        $container = new Container();

        \Parity\assertType($container->get($id), \parity_expected_2958d416d08aa5a472d7());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnObjectWhenGetWithUnionOfServiceIds(bool $useService): void
    {
        $container = new Container();

        \Parity\assertType($container->get($useService ? 'service' : 'closure'), \parity_expected_2958d416d08aa5a472d7());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnObjectWhenGetWithUnknownId(): void
    {
        $container = new Container();

        \Parity\assertType($container->get('unknown-service'), \parity_expected_2958d416d08aa5a472d7());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceClassWhenGetParamsAreUnpacked(): void
    {
        $container = new Container();

        \Parity\assertType($container->get('service', ...[[]]), \parity_expected_360996eb2a61b983d325());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetByNamedArguments(): void
    {
        $container = new Container();

        \Parity\assertType($container->get(class: 'service'), \parity_expected_360996eb2a61b983d325());
        \Parity\assertType($container->get(params: [], class: 'singleton-string'), \parity_expected_a9c90bb1f4c06e6f7920());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetDefinitionClosure(): void
    {
        $container = new Container();

        \Parity\assertType($container->get('closure'), \parity_expected_54623e285e6e2f48d64c());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetDefinitionService(): void
    {
        $container = new Container();

        \Parity\assertType($container->get('service'), \parity_expected_360996eb2a61b983d325());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetRealClassNotInServiceMap(): void
    {
        $container = new Container();

        \Parity\assertType($container->get(Exception::class), \parity_expected_b4fe3d529dee467ddcf2());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetServiceMapWithStringConstant(): void
    {
        $container = new Container();

        \Parity\assertType($container->get('yii2\extensions\phpstan\tests\support\stub\MyActiveRecord'), \parity_expected_a9c90bb1f4c06e6f7920());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetSingletonClosure(): void
    {
        $container = new Container();

        \Parity\assertType($container->get('singleton-closure'), \parity_expected_54623e285e6e2f48d64c());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetSingletonService(): void
    {
        $container = new Container();

        \Parity\assertType($container->get('singleton-service'), \parity_expected_360996eb2a61b983d325());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetSingletonString(): void
    {
        $container = new Container();

        \Parity\assertType($container->get('singleton-string'), \parity_expected_a9c90bb1f4c06e6f7920());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetWithConditional(): void
    {
        $container = new Container();

        $useService = (bool) random_int(0, 1);

        $result = $useService ? $container->get('singleton-service') : $container->get('closure');

        \Parity\assertType($result, \parity_expected_ddb277f0ac6abeaaeeae());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceWhenGetWithParameters(): void
    {
        $container = new Container();

        $params = ['flag' => true];

        \Parity\assertType($container->get(MyActiveRecord::class, $params), \parity_expected_a9c90bb1f4c06e6f7920());
    }
}
