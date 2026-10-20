<?php

declare(strict_types=1);

namespace Regression\PropertyGenerics;

use yii\db\ActiveRecord;

use function Mago\confirm;

/**
 * @template T
 * @property T $value
 */
class TypedRecord extends ActiveRecord {}

/** @extends TypedRecord<float> */
final class DecimalRecord extends TypedRecord {}

function inheritedProperty(DecimalRecord $record): void
{
    confirm($record->value, 'float');
    confirm($record->getAttribute('value'), 'float');
    confirm(DecimalRecord::find()->asArray()->one(), "array{'value': float}|null");
}
