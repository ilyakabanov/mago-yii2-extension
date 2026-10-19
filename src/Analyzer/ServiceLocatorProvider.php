<?php

declare(strict_types=1);

namespace yii2\extensions\mago\Analyzer;

use Mago\Sdk\Analyzer\MethodReturnTypeProvider;
use Mago\Sdk\Analyzer\MethodTarget;
use Mago\Sdk\Analyzer\ReturnTypeProviderContext;
use Mago\Sdk\Analyzer\Type;
use yii2\extensions\mago\ServiceMap;

final class ServiceLocatorProvider implements MethodReturnTypeProvider
{
    public function __construct(
        private readonly ServiceMap $map,
    ) {}

    public function getTargets(): array
    {
        return [MethodTarget::exact('yii\\di\\ServiceLocator', 'get')];
    }

    public function getReturnType(ReturnTypeProviderContext $context): ?Type
    {
        $argument = $context->invocation->getArgument(0, 'id');
        if ($argument === null || $argument->unpacked) {
            return null;
        }
        $id = $argument->type?->getLiteralString();
        if ($id === null) {
            return null;
        }

        $class = $this->map->getComponentClassById($id);
        $type = $class !== null ? Type::namedObject($class) : null;
        if ($type === null && $this->map->isUnresolvedComponent($id)) {
            $type = Type::object();
        }
        if ($type === null) {
            return null;
        }

        $throw = $context->invocation->getArgument(1, 'throwException');

        return $throw === null || TypeTools::booleanArgument($throw) === true
            ? $type
            : Type::union($type, Type::null());
    }
}
