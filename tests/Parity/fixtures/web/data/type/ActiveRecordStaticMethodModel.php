<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveRecord;

use function PHPStan\Testing\assertType;

/**
 * Model calling its own static query methods for `static::find()` and `self::find()` type assertions.
 *
 * @property int $id
 * @property string $sku
 */
class ActiveRecordStaticMethodModel extends ActiveRecord
{
    public function testReturnStaticQueryWhenFindCalledInsideModel(): void
    {
        \Parity\assertType(static::find(), \parity_expected_1548ce0d205d753d085e());
        \Parity\assertType(self::find(), \parity_expected_1548ce0d205d753d085e());
        \Parity\assertType(static::find()->asArray()->one(), \parity_expected_8d620fdc1dc431cd5520());
    }
}
