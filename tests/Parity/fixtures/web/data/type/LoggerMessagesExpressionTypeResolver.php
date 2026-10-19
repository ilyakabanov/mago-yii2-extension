<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\log\{FileTarget, Logger, Target};
use yii2\extensions\phpstan\tests\support\stub\MessageContainer;

use function PHPStan\Testing\{assertSuperType, assertType};

/**
 * Type assertion fixture for Yii logger message inference.
 */
final class LoggerMessagesExpressionTypeResolver
{
    public function testLoggerMessagesExposeTheRuntimeTupleShape(): void
    {
        $logger = new Logger();

        \Parity\assertType($logger->messages, \parity_expected_80ec571b7500670e31c9());

        foreach ($logger->messages as $message) {
            \Parity\assertSuperType($message, \parity_expected_ce5feca8e2542e863521());
            \Parity\assertType($message[0], \parity_expected_2ed4ba30253658c24e98());
            \Parity\assertType($message[1], \parity_expected_6da88c34ba124c41f977());
            \Parity\assertType($message[2], \parity_expected_473287f8298dba7163a8());
            \Parity\assertType($message[3], \parity_expected_76a7e234dc54e9c605b2());
            \Parity\assertType($message[4], \parity_expected_96d92ede305feb22d141());
            \Parity\assertType($message[5] ?? null, \parity_expected_586cf00f65235bf62aa9());
        }
    }

    public function testTargetFilteringDefersToTheDeclaredTypeWhenArgumentIsUnpacked(): void
    {
        // the declared type follows the Yii PHPDoc; a resolved call would be `array{}`, making the comparison `true`
        \Parity\assertSuperType(Target::filterMessages(...[[]]), \parity_expected_dbe42cc09c16704aa3d6());
        \Parity\assertType(Target::filterMessages(...[[]]) === [], \parity_expected_b760f44fa5965c2474a3());
    }

    public function testTargetFilteringKeepsAnEmptyMessageArray(): void
    {
        \Parity\assertType(Target::filterMessages([]), \parity_expected_41454d37b8f209b8db4e());
    }

    public function testTargetMessagesAndFilteringPreserveTheTupleShape(): void
    {
        $logger = new Logger();
        $target = new FileTarget();

        \Parity\assertType($target->messages, \parity_expected_9a34e396e0ee54858478());
        \Parity\assertType(Target::filterMessages($logger->messages), \parity_expected_80ec571b7500670e31c9());

        foreach ($target->messages as $message) {
            \Parity\assertSuperType($message, \parity_expected_ce5feca8e2542e863521());
        }
    }

    public function testUnrelatedExpressionsKeepTheirNativeTypes(): void
    {
        $logger = new Logger();
        $container = new MessageContainer();

        \Parity\assertType($logger->flushInterval, \parity_expected_6da88c34ba124c41f977());
        \Parity\assertType($container->messages, \parity_expected_f8836dfea71a673cc20c());
    }
}
