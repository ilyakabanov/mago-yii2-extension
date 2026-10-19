<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii2\extensions\phpstan\tests\support\stub\Ticket;

use function PHPStan\Testing\assertType;

/**
 * Subclass of {@see Ticket} calling its bound query class through `self`, `static`, and `parent`.
 */
class ActiveRecordQueryTicketRecord extends Ticket
{
    public function testReturnStaticModelWhenFindCalledInsideModel(): void
    {
        \Parity\assertType(self::find()->one(), \parity_expected_0ec2bd4416c26fe1e944());
        \Parity\assertType(static::find()->open()->all(), \parity_expected_3391a77b109787a4abf9());
        \Parity\assertType(parent::find()->one(), \parity_expected_0ec2bd4416c26fe1e944());
    }
}
