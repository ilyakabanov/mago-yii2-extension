<?php

declare(strict_types=1);

namespace yii2\extensions\mago\Analyzer;

use Mago\Sdk\Analyzer\MethodReturnTypeProvider;
use Mago\Sdk\Analyzer\MethodTarget;
use Mago\Sdk\Analyzer\ReturnTypeProviderContext;
use Mago\Sdk\Analyzer\Type;
use yii2\extensions\mago\ServiceMap;

final class ContainerProvider implements MethodReturnTypeProvider
{
    public function __construct(
        private readonly ServiceMap $map,
    ) {}

    public function getTargets(): array
    {
        return [MethodTarget::exact('yii\\di\\Container', 'get')];
    }

    public function getReturnType(ReturnTypeProviderContext $context): ?Type
    {
        $argument = $context->invocation->getArgument(0, 'class');
        if ($argument === null || $argument->unpacked) {
            return null;
        }

        $id = $argument->type?->getLiteralString();
        if ($id === null) {
            return null;
        }

        $class = $this->map->getServiceById($id);
        if ($class !== null) {
            return Type::namedObject($class);
        }

        return $this->map->isUnresolvedService($id) ? Type::object() : null;
    }
}
