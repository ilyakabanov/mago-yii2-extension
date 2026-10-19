<?php

declare(strict_types=1);

namespace yii2\extensions\mago\Analyzer;

use Mago\Sdk\Analyzer\PropertyTarget;
use Mago\Sdk\Analyzer\PropertyType;
use Mago\Sdk\Analyzer\PropertyTypeProvider;
use Mago\Sdk\Analyzer\PropertyTypeProviderContext;
use yii2\extensions\mago\ServiceMap;

final class BehaviorProvider implements PropertyTypeProvider
{
    public function __construct(
        private readonly ServiceMap $map,
    ) {}

    public function getTargets(): array
    {
        return [PropertyTarget::allProperties('yii\\base\\Component')];
    }

    public function getPropertyType(PropertyTypeProviderContext $context): ?PropertyType
    {
        $class = $context->access->class;
        $property = $context->access->property;
        $owner = $context->codebase->getClassLike($class);
        if ($owner === null || PropertyResolver::propertyName($owner->properties, $property) !== null) {
            return null;
        }

        foreach ($this->map->getBehaviorsByClassName($class) as $behavior) {
            $type = PropertyResolver::resolve($context->codebase, $behavior, $property);
            if ($type !== null) {
                return $type;
            }
        }

        return null;
    }
}
