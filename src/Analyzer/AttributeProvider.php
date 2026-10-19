<?php

declare(strict_types=1);

namespace yii2\extensions\mago\Analyzer;

use Mago\Sdk\Analyzer\MethodReturnTypeProvider;
use Mago\Sdk\Analyzer\MethodTarget;
use Mago\Sdk\Analyzer\ReturnTypeProviderContext;
use Mago\Sdk\Analyzer\Type;
use yii2\extensions\mago\ServiceMap;

final class AttributeProvider implements MethodReturnTypeProvider
{
    /** Framework magic properties describe virtual getters rather than stored ActiveRecord attributes. */
    private const FRAMEWORK_ATTRIBUTE_BOUNDARIES = [
        'yii\\db\\activerecord',
        'yii\\db\\baseactiverecord',
        'yii\\db\\activerecordinterface',
        'yii\\base\\model',
        'yii\\base\\component',
        'yii\\base\\baseobject',
        'yii\\base\\arrayable',
        'yii\\base\\arrayabletrait',
        'yii\\base\\configurable',
    ];

    public function __construct(
        private readonly ServiceMap $map,
    ) {}

    public function getTargets(): array
    {
        return [MethodTarget::exact('yii\\db\\BaseActiveRecord', 'getAttribute')];
    }

    public function getReturnType(ReturnTypeProviderContext $context): ?Type
    {
        foreach ($context->invocation->arguments as $argument) {
            if ($argument->unpacked) {
                return Type::mixed();
            }
        }

        $attribute = $context->invocation->getArgument(0, 'name')?->type?->getLiteralString();
        $class = PropertyResolver::receiverClass($context->invocation->receiverType);
        if ($attribute === null || $class === null) {
            return Type::mixed();
        }

        foreach ([$class, ...$this->map->getBehaviorsByClassName($class)] as $owner) {
            $property = PropertyResolver::magic(
                $context->codebase,
                $owner,
                $attribute,
                self::FRAMEWORK_ATTRIBUTE_BOUNDARIES,
            );
            if ($property !== null && $property->readType !== null) {
                return $property->writeType !== null ? $property->readType : Type::mixed();
            }
        }

        return Type::mixed();
    }
}
