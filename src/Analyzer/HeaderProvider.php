<?php

declare(strict_types=1);

namespace yii2\extensions\mago\Analyzer;

use Mago\Sdk\Analyzer\MethodReturnTypeProvider;
use Mago\Sdk\Analyzer\MethodTarget;
use Mago\Sdk\Analyzer\ReturnTypeProviderContext;
use Mago\Sdk\Analyzer\Type;

final class HeaderProvider implements MethodReturnTypeProvider
{
    public function getTargets(): array
    {
        return [MethodTarget::exact('yii\\web\\HeaderCollection', 'get')];
    }

    public function getReturnType(ReturnTypeProviderContext $context): ?Type
    {
        foreach ($context->invocation->arguments as $argument) {
            if ($argument->unpacked) {
                return null;
            }
        }

        $default = $context->invocation->getArgument(1, 'default');
        $first = $context->invocation->getArgument(2, 'first');

        return self::infer($default?->type, $first?->type ?? Type::true());
    }

    public static function infer(?Type $default, Type $first): Type
    {
        $array = Type::array(TypeTools::arrayKey(), Type::string());
        $type = match ($first->getLiteralBool()) {
            true => Type::string(),
            false => $array,
            null => Type::union(Type::string(), $array),
        };

        return $default === null || TypeTools::mayBeNull($default) ? Type::union($type, Type::null()) : $type;
    }
}
