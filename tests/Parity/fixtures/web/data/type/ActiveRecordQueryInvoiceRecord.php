<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii2\extensions\phpstan\tests\support\stub\Invoice;

use function PHPStan\Testing\assertType;

/**
 * Subclass of the Gii-generated {@see Invoice} model calling its query methods through `self`, `static`, and `parent`.
 */
final class ActiveRecordQueryInvoiceRecord extends Invoice
{
    public function testReturnInvoiceWhenSelfScopeCalledInsideModel(): void
    {
        \Parity\assertType(self::find()->paid()->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType(self::find()->paid()->all(), \parity_expected_1d2376a17f7fd046d351());
    }

    public function testReturnStaticModelWhenFindCalledInsideModel(): void
    {
        \Parity\assertType(self::find(), \parity_expected_9cede1fbe58931d6e327());
        \Parity\assertType(self::find()->one(), \parity_expected_4722e53dd87a6e89d736());
        \Parity\assertType(self::find()->where(['id' => 1])->all(), \parity_expected_5e2961f14263f0e76614());
        \Parity\assertType(parent::find()->one(), \parity_expected_4722e53dd87a6e89d736());
        \Parity\assertType($this::find()->one(), \parity_expected_4722e53dd87a6e89d736());
        \Parity\assertType(self::find()->asArray()->one(), \parity_expected_232678477d739d948205());

        foreach (parent::find()->each() as $invoice) {
            \Parity\assertType($invoice, \parity_expected_b9917d8edfff7ddaaad6());
        }
    }

    public static function testReturnStaticModelWhenFindCalledInStaticMethod(): void
    {
        \Parity\assertType(self::find()->one(), \parity_expected_4722e53dd87a6e89d736());
        \Parity\assertType(self::find()->one(), \parity_expected_4722e53dd87a6e89d736());
    }
}
