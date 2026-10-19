<?php

declare(strict_types=1);

namespace yii2\extensions\mago\Analyzer;

use Mago\Sdk\Analyzer\MethodReturnTypeProvider;
use Mago\Sdk\Analyzer\MethodTarget;
use Mago\Sdk\Analyzer\ReturnTypeProviderContext;
use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\KeyedArrayType;
use Mago\Sdk\Analyzer\Type\ListType;

final class FilterMessagesProvider implements MethodReturnTypeProvider
{
    public function getTargets(): array
    {
        return [MethodTarget::exact('yii\\log\\Target', 'filterMessages')];
    }

    public function getReturnType(ReturnTypeProviderContext $context): ?Type
    {
        foreach ($context->invocation->arguments as $argument) {
            if ($argument->unpacked) {
                return null;
            }
        }
        $type = $context->invocation->getArgument(0, 'messages')?->type;
        if ($type === null) {
            return null;
        }
        $results = [];
        foreach ($type->atomicTypes as $atomic) {
            if ($atomic instanceof ListType) {
                $results[] = Type::array(Type::int(), $atomic->elementType);
                continue;
            }
            if (!$atomic instanceof KeyedArrayType) {
                return null;
            }
            $keys = $atomic->keyType !== null ? [$atomic->keyType] : [];
            $values = $atomic->valueType !== null ? [$atomic->valueType] : [];
            foreach ($atomic->knownItems ?? [] as $item) {
                $keys[] = is_int($item->key->value)
                    ? Type::literalInt($item->key->value)
                    : Type::literalString((string) $item->key->value);
                $values[] = $item->type;
            }
            $results[] = $values === []
                ? Type::fromAtomic($atomic)
                : Type::array(TypeTools::union($keys), TypeTools::union($values));
        }

        return TypeTools::union($results);
    }
}
