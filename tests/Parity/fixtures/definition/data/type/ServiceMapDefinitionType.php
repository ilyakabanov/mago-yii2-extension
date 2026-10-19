<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use ArrayIterator;
use ArrayObject;
use SplObjectStorage;
use Yii;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;
use yii2\extensions\phpstan\tests\support\stub\PlainService;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for components and container services declared by closures and class name strings.
 *
 * Verifies that a closure with a class return type resolves to that class, even when that class is an aliased container
 * ID, that a class name string resolves with or without a leading backslash, that a core component configured without
 * a class keeps the type declared by the application, that a component defined by a `yii\di\Instance` reference is that
 * reference, and that components and services with an unknown class, and services naming one, are `object`, even under
 * a class name ID.
 */
final class ServiceMapDefinitionType
{
    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnApplicationDeclaredTypeForCoreComponentWithoutClass(): void
    {
        \Parity\assertType(Yii::$app->request, \parity_expected_8be172a009e0cda557cf());
        \Parity\assertType(Yii::$app->get('request'), \parity_expected_2958d416d08aa5a472d7());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnClosureReturnClassForComponent(): void
    {
        \Parity\assertType(Yii::$app->closureView, \parity_expected_b2fa295eac0bd39085c3());
        \Parity\assertType(Yii::$app->get('closureView'), \parity_expected_b2fa295eac0bd39085c3());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnClosureReturnClassForService(): void
    {
        \Parity\assertType(Yii::$container->get('typedService'), \parity_expected_54623e285e6e2f48d64c());
        \Parity\assertType(Yii::$container->get('closureCache'), \parity_expected_402ba595b41709700e3b());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnInstanceForComponentDefinedByInstanceReference(): void
    {
        \Parity\assertType(Yii::$app->get('instanceComponent'), \parity_expected_90e9033067b6d342d05f());
        \Parity\assertType(Yii::$app->instanceComponent, \parity_expected_90e9033067b6d342d05f());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnObjectForUnresolvableComponent(): void
    {
        \Parity\assertType(Yii::$app->get('untypedClosure'), \parity_expected_2958d416d08aa5a472d7());
        \Parity\assertType(Yii::$app->untypedClosure, \parity_expected_2958d416d08aa5a472d7());
        \Parity\assertType(Yii::$app->get(ArrayIterator::class), \parity_expected_2958d416d08aa5a472d7());
        \Parity\assertType(Yii::$app->user, \parity_expected_2958d416d08aa5a472d7());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnObjectForUnresolvableService(): void
    {
        \Parity\assertType(Yii::$container->get('untypedService'), \parity_expected_2958d416d08aa5a472d7());
        \Parity\assertType(Yii::$container->get('untypedServiceAlias'), \parity_expected_2958d416d08aa5a472d7());
        \Parity\assertType(Yii::$container->get(ArrayObject::class), \parity_expected_2958d416d08aa5a472d7());
        \Parity\assertType(Yii::$container->get(SplObjectStorage::class), \parity_expected_2958d416d08aa5a472d7());
        \Parity\assertType(Yii::$app->get(ArrayObject::class), \parity_expected_2958d416d08aa5a472d7());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnResolvedClassForServiceNamingAnotherId(): void
    {
        \Parity\assertType(Yii::$container->get('mailer.alias'), \parity_expected_b2fa295eac0bd39085c3());
        \Parity\assertType(Yii::$container->get('instanceService'), \parity_expected_54623e285e6e2f48d64c());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     * @throws NotInstantiableException if a class or service can't be instantiated.
     */
    public function testReturnServiceIdClassForArrayWithoutClass(): void
    {
        \Parity\assertType(Yii::$container->get(PlainService::class), \parity_expected_0c66d470c71ebe608423());
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnStringClassForComponent(): void
    {
        \Parity\assertType(Yii::$app->stringView, \parity_expected_b2fa295eac0bd39085c3());
        \Parity\assertType(Yii::$app->get('stringView'), \parity_expected_b2fa295eac0bd39085c3());
        \Parity\assertType(Yii::$app->prefixedView, \parity_expected_b2fa295eac0bd39085c3());
    }
}
