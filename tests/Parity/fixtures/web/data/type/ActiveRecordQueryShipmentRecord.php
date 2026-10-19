<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii2\extensions\phpstan\tests\support\stub\Shipment;

use function PHPStan\Testing\assertType;

/**
 * Subclass of {@see Shipment} calling its bare query class through `self`, `static`, and `parent`.
 */
class ActiveRecordQueryShipmentRecord extends Shipment
{
    public function testReturnStaticModelWhenFindCalledInsideModel(): void
    {
        \Parity\assertType(self::find()->pending()->one(), \parity_expected_ad2f46bf332007712e10());
        \Parity\assertType(static::find()->shipped()->all(), \parity_expected_166bd698fef7ba6754e8());
        \Parity\assertType(parent::find()->one(), \parity_expected_ad2f46bf332007712e10());

        foreach (static::find()->batch() as $shipments) {
            \Parity\assertType($shipments, \parity_expected_166bd698fef7ba6754e8());
        }
    }
}
