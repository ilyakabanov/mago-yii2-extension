<?php

declare(strict_types=1);

namespace yii2\extensions\mago\Analyzer;

use Mago\Sdk\Analyzer\PropertyTarget;
use Mago\Sdk\Analyzer\PropertyType;
use Mago\Sdk\Analyzer\PropertyTypeProvider;
use Mago\Sdk\Analyzer\PropertyTypeProviderContext;
use Mago\Sdk\Analyzer\Type;
use yii2\extensions\mago\ParamsTypeBuilder;
use yii2\extensions\mago\ServiceMap;

final class ApplicationProvider implements PropertyTypeProvider
{
    /** @param array<string, string> $genericComponents */
    public function __construct(
        private readonly ServiceMap $map,
        private readonly array $genericComponents = ['user' => 'identityClass'],
    ) {}

    public function getTargets(): array
    {
        return [
            PropertyTarget::exact('yii\\BaseYii', 'app'),
            PropertyTarget::exact('yii\\base\\Module', 'params'),
            PropertyTarget::allProperties('yii\\base\\Application'),
        ];
    }

    public function getPropertyType(PropertyTypeProviderContext $context): ?PropertyType
    {
        $property = $context->access->property;
        $class = $context->access->class;
        if ($property === 'app' && in_array(strtolower($class), ['yii', 'yii\\baseyii'], true)) {
            return $this->both(Type::namedObject($this->map->getApplicationType() ?: 'yii\\base\\Application'));
        }

        if ($property === 'params') {
            $owner = $class;
            while (strtolower($owner) !== 'yii\\base\\module') {
                $metadata = $context->codebase->getClassLike($owner);
                if ($metadata === null) {
                    break;
                }
                if (PropertyResolver::propertyName($metadata->properties, 'params') !== null) {
                    return PropertyResolver::native($context->codebase, $owner, 'params');
                }
                if ($metadata->directParentClass === null) {
                    break;
                }
                $owner = $metadata->directParentClass;
            }
            return new PropertyType(
                ParamsTypeBuilder::build($this->map->getParams()),
                Type::array(TypeTools::arrayKey(), Type::mixed()),
            );
        }

        $configuredClass = $this->map->getApplicationType();
        if ($configuredClass !== '' && $context->codebase->getClassLike($configuredClass) !== null) {
            $class = $configuredClass;
        }

        $native = PropertyResolver::native($context->codebase, $class, $property);
        if ($native !== null) {
            return $native;
        }

        $componentClass = $this->map->getComponentClassById($property);
        if ($componentClass !== null) {
            return $this->both($this->componentType($componentClass, $property));
        }

        $unresolved = $this->map->isUnresolvedComponent($property);
        if (!$unresolved || $this->map->isComponentWithoutClass($property)) {
            $declared = PropertyResolver::resolve($context->codebase, $class, $property);
            if ($declared !== null) {
                return $declared;
            }
        }

        return $unresolved ? $this->both(Type::object()) : null;
    }

    private function componentType(string $class, string $property): Type
    {
        $genericProperty = $this->genericComponents[$property] ?? null;
        $definition = $this->map->getComponentDefinitionById($property);
        $genericClass = $genericProperty !== null ? $definition[$genericProperty] ?? null : null;

        return is_string($genericClass) && $genericClass !== ''
            ? Type::namedObject($class, Type::namedObject($genericClass))
            : Type::namedObject($class);
    }

    private function both(Type $type): PropertyType
    {
        return new PropertyType($type, $type);
    }
}
