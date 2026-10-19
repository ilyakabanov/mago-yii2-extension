<?php

declare(strict_types=1);

namespace yii2\extensions\mago\Analyzer;

use Mago\Sdk\Analyzer\CallableSignatureProvider;
use Mago\Sdk\Analyzer\CallableSignatureProviderContext;
use Mago\Sdk\Analyzer\Codebase;
use Mago\Sdk\Analyzer\EffectiveCallableSignature;
use Mago\Sdk\Analyzer\InvocationKind;
use Mago\Sdk\Analyzer\Metadata\FunctionLikeMetadata;
use Mago\Sdk\Analyzer\Metadata\MetadataFlags;
use Mago\Sdk\Analyzer\MethodReturnTypeProvider;
use Mago\Sdk\Analyzer\MethodTarget;
use Mago\Sdk\Analyzer\ReturnTypeProviderContext;
use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\CallableParameter;
use Mago\Sdk\Analyzer\Type\Visibility;
use yii2\extensions\mago\ServiceMap;

final class BehaviorMethodProvider implements MethodReturnTypeProvider, CallableSignatureProvider
{
    public function __construct(
        private readonly ServiceMap $map,
    ) {}

    public function getTargets(): array
    {
        return [MethodTarget::allMethods('yii\\base\\Component')];
    }

    public function getReturnType(ReturnTypeProviderContext $context): ?Type
    {
        if ($context->invocation->kind !== InvocationKind::InstanceMethod) {
            return null;
        }

        $class = PropertyResolver::receiverClass($context->invocation->receiverType);
        $method = $class !== null ? $this->findMethod($context->codebase, $class, $context->invocation->name) : null;

        return $method !== null ? $method->returnType?->type ?? Type::mixed() : null;
    }

    public function getCallableSignature(CallableSignatureProviderContext $context): ?EffectiveCallableSignature
    {
        if ($context->invocation->kind !== InvocationKind::InstanceMethod) {
            return null;
        }

        $class = PropertyResolver::receiverClass($context->invocation->receiverType);
        $method = $class !== null ? $this->findMethod($context->codebase, $class, $context->invocation->name) : null;
        if ($method === null) {
            return null;
        }

        $parameters = [];
        foreach ($method->parameters as $parameter) {
            $parameters[] = new CallableParameter(
                name: '$' . ltrim($parameter->name, '$'),
                type: $parameter->type?->type,
                closureThisType: $parameter->closureThisType?->type,
                byReference: $parameter->flags->contains(MetadataFlags::BY_REFERENCE),
                variadic: $parameter->flags->contains(MetadataFlags::VARIADIC),
                hasDefault: $parameter->flags->contains(MetadataFlags::HAS_DEFAULT),
                outType: $parameter->outType?->type,
            );
        }

        return new EffectiveCallableSignature(
            $parameters,
            displayName: ($class ?? 'yii\\base\\Component') . '::' . $context->invocation->name,
        );
    }

    private function findMethod(Codebase $codebase, string $class, string $name): ?FunctionLikeMetadata
    {
        $owner = $codebase->getClassLike($class);
        if ($owner === null || in_array(strtolower($name), array_map('strtolower', $owner->methods), true)) {
            return null;
        }

        foreach ($this->map->getBehaviorsByClassName($class) as $behavior) {
            $metadata = $codebase->getClassLike($behavior);
            if ($metadata === null || !in_array(strtolower($name), array_map('strtolower', $metadata->methods), true)) {
                continue;
            }

            $method = $codebase->getMethod($behavior, $name);
            if ($method !== null && $method->visibility === Visibility::Public) {
                return $method;
            }
        }

        return null;
    }
}
