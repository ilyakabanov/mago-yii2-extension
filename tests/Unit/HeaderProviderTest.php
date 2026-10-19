<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use Mago\Sdk\Analyzer\Type;
use PHPUnit\Framework\TestCase;
use yii2\extensions\mago\Analyzer\HeaderProvider;

final class HeaderProviderTest extends TestCase
{
    public function testDefaultRemovesNullAndFirstSelectsTheValueType(): void
    {
        self::assertSame('string|null', (string) HeaderProvider::infer(null, Type::true()));
        self::assertSame('string', (string) HeaderProvider::infer(Type::string(), Type::true()));
        self::assertSame(
            'array<array-key, string>',
            (string) HeaderProvider::infer(Type::array(Type::int(), Type::string()), Type::false()),
        );
        self::assertSame(
            'string|array<array-key, string>',
            (string) HeaderProvider::infer(Type::string(), Type::bool()),
        );
        self::assertSame(
            'string|null',
            (string) HeaderProvider::infer(Type::union(Type::string(), Type::null()), Type::true()),
        );
        self::assertSame('string|null', (string) HeaderProvider::infer(Type::mixed(), Type::true()));
    }
}
