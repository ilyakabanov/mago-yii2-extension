<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii2\extensions\phpstan\tests\support\stub\Invoice;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for the inferred query of {@see Invoice::find()} with the union ordering methods and the
 * generic {@see \yii\db\BatchQueryResult} that only Yii 22 declares.
 */
final class ActiveRecordQueryDynamicStaticMethodUnionBatchType
{
    public function testKeepInferredQueryWhenUnionOrderingMethodIsCalled(): void
    {
        \Parity\assertType(Invoice::find()->unionOrderBy('id'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->addUnionOrderBy('id'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->unionLimit(10), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->unionOffset(10), \parity_expected_589914032359d26f0301());
    }

    public function testReturnInvoiceBatchQueryResultWhenBatchOrEachIsCalled(): void
    {
        \Parity\assertType(Invoice::find()->batch(), \parity_expected_d9bc4917cf222368bbc5());
        \Parity\assertType(Invoice::find()->each(), \parity_expected_2cce1faf400463532b70());
    }
}
