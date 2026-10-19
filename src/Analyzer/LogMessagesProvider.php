<?php

declare(strict_types=1);

namespace yii2\extensions\mago\Analyzer;

use Mago\Sdk\Analyzer\PropertyTarget;
use Mago\Sdk\Analyzer\PropertyType;
use Mago\Sdk\Analyzer\PropertyTypeProvider;
use Mago\Sdk\Analyzer\PropertyTypeProviderContext;
use Mago\Sdk\Analyzer\Type;
use Mago\Sdk\Analyzer\Type\ArrayItem;
use Mago\Sdk\Analyzer\Type\ArrayKey;
use Mago\Sdk\Analyzer\Type\ArrayKeyKind;
use Mago\Sdk\Analyzer\Type\ClassLikeStringKind;
use Mago\Sdk\Analyzer\Type\ClassLikeStringType;
use Mago\Sdk\Analyzer\Type\ClassLikeStringVariant;
use Mago\Sdk\Analyzer\Type\KeyedArrayType;
use Mago\Sdk\Analyzer\Type\ScalarType;
use Mago\Sdk\Analyzer\Type\ScalarTypeKind;

final class LogMessagesProvider implements PropertyTypeProvider
{
    public function getTargets(): array
    {
        return [
            PropertyTarget::exact('yii\\log\\Logger', 'messages'),
            PropertyTarget::exact('yii\\log\\Target', 'messages'),
        ];
    }

    public function getPropertyType(PropertyTypeProviderContext $context): ?PropertyType
    {
        $message = self::messageType($context->codebase->classExists('yii\\log\\PsrMessage'));
        $class = $context->codebase->getClass($context->access->class);
        $isTarget = strcasecmp($context->access->class, 'yii\\log\\Target') === 0;
        foreach ($class?->parentClasses ?? [] as $parent) {
            $isTarget = $isTarget || strcasecmp($parent, 'yii\\log\\Target') === 0;
        }
        $type = $isTarget ? Type::list($message) : Type::array(TypeTools::arrayKey(), $message);

        return new PropertyType($type, $type);
    }

    public static function messageType(bool $psr): Type
    {
        $payload = [Type::string(), Type::array(TypeTools::arrayKey(), Type::mixed()), Type::namedObject('Throwable')];
        if ($psr) {
            $payload[] = Type::namedObject('yii\\log\\PsrMessage');
        }
        $frame = self::shape([
            'file' => Type::string(),
            'line' => Type::int(),
            'function' => Type::string(),
            'class' => Type::fromAtomic(
                new ScalarType(
                    ScalarTypeKind::ClassLikeString,
                    new ClassLikeStringType(ClassLikeStringVariant::Any, ClassLikeStringKind::Class_),
                ),
            ),
            'type' => Type::string(),
        ], ['function', 'class', 'type']);

        return self::shape([
            TypeTools::union($payload),
            Type::int(),
            Type::string(),
            Type::float(),
            Type::list($frame),
            Type::int(),
        ], [5]);
    }

    /** @param array<array-key, Type> $types @param list<array-key> $optional */
    private static function shape(array $types, array $optional): Type
    {
        $items = [];
        foreach ($types as $key => $type) {
            $items[] = new ArrayItem(
                new ArrayKey(is_int($key) ? ArrayKeyKind::Integer : ArrayKeyKind::String, $key),
                in_array($key, $optional, true),
                $type,
            );
        }

        return Type::fromAtomic(new KeyedArrayType($items, null, null, $types !== []));
    }
}
