<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use Mago\Sdk\Analyzer\Type\KeyedArrayType;
use PHPUnit\Framework\TestCase;
use yii2\extensions\mago\Analyzer\LogMessagesProvider;

final class LogTypeTest extends TestCase
{
    public function testMessageTupleAndTraceKeepTheirOptionalFields(): void
    {
        $tuple = LogMessagesProvider::messageType(false)->atomicTypes[0];
        self::assertInstanceOf(KeyedArrayType::class, $tuple);
        self::assertCount(6, $tuple->knownItems);
        self::assertFalse($tuple->knownItems[4]->optional);
        self::assertTrue($tuple->knownItems[5]->optional);
        self::assertSame('int', (string) $tuple->knownItems[5]->type);
        self::assertCount(3, $tuple->knownItems[0]->type->atomicTypes);
        $psrTuple = LogMessagesProvider::messageType(true)->atomicTypes[0];
        self::assertCount(4, $psrTuple->knownItems[0]->type->atomicTypes);
    }
}
