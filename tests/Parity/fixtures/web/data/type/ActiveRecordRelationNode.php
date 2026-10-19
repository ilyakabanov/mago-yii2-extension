<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveRecord;

use function PHPStan\Testing\assertType;

/**
 * Self-referencing model for `static::class` relation type assertions.
 *
 * @property int $id
 * @property int|null $parent_id
 */
class ActiveRecordRelationNode extends ActiveRecord
{
    public function testReturnStaticQueryWhenHasManyWithStaticClass(): void
    {
        \Parity\assertType($this->hasMany(static::class, ['parent_id' => 'id']), \parity_expected_14e6b7702750d4c01ae4());
        \Parity\assertType($this->hasMany(static::class, ['parent_id' => 'id'])->asArray()->all(), \parity_expected_64f33bb95234ce0c8147());
    }
}
