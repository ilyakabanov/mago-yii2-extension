<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii2\extensions\phpstan\tests\support\stub\{CreditInvoiceQuery, Invoice, InvoiceQuery, ShipmentQuery};

use function is_array;
use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for the model derived from the `one()` and `all()` overrides of a non-generic query class
 * reached without a call site that names the model.
 *
 * Covers the Gii-generated {@see InvoiceQuery} and the {@see CreditInvoiceQuery} deriving from it as a typed parameter,
 * a typed property, a returned value, and the result of a scope returning `self`, through every row-dependent
 * {@see \yii\db\ActiveQuery} method, and a bare query class that keeps PHPStan's own answer, whose rows may still be
 * arrays.
 */
final class ActiveQueryDerivedModelReturnType
{
    private CreditInvoiceQuery $creditInvoiceQuery;
    private InvoiceQuery $invoiceQuery;

    public function __construct()
    {
        $this->invoiceQuery = Invoice::find();
        $this->creditInvoiceQuery = new CreditInvoiceQuery(Invoice::class);
    }

    public function testKeepNativeTypeWhenBareQueryClassIsTypedParameter(ShipmentQuery $query): void
    {
        \Parity\assertType(is_array($query->one()), \parity_expected_b760f44fa5965c2474a3());
        \Parity\assertType(is_array($query->pending()->all()[0] ?? null), \parity_expected_b760f44fa5965c2474a3());
        \Parity\assertType($query->asArray(), \parity_expected_bf9b6c8b6c559e197a05());
    }

    public function testKeepQueryClassWhenFluentMethodOrScopeIsCalled(InvoiceQuery $query): void
    {
        \Parity\assertType($query->where(['id' => 1]), \parity_expected_799261f07fad4d570694());
        \Parity\assertType($query->paid(), \parity_expected_799261f07fad4d570694());
        \Parity\assertType($query->asArray(false), \parity_expected_799261f07fad4d570694());
        \Parity\assertType(Invoice::find()->paid(), \parity_expected_799261f07fad4d570694());
        \Parity\assertType($this->creditInvoiceQuery->refunded()->orderBy('id'), \parity_expected_1a80d0ff194a34d2d4ff());
    }

    public function testReturnInvoiceWhenDerivedQueryIsTypedParameter(CreditInvoiceQuery $query): void
    {
        \Parity\assertType($query->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType($query->all(), \parity_expected_1d2376a17f7fd046d351());
        \Parity\assertType($query->where(['id' => 1])->refunded()->limit(1)->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType($query->refunded()->asArray()->one(), \parity_expected_232678477d739d948205());
        \Parity\assertType($query->asArray()->all(), \parity_expected_03edae2e364547df8b19());
        \Parity\assertType($query->asArray(false)->one(), \parity_expected_6e2d2921b8c12eaf4ed1());

        foreach ($query->batch() as $invoices) {
            \Parity\assertType($invoices, \parity_expected_1d2376a17f7fd046d351());
        }

        foreach ($query->refunded()->each() as $invoice) {
            \Parity\assertType($invoice, \parity_expected_4e7debefd5fcd892f188());
        }
    }

    public function testReturnInvoiceWhenQueryIsReturnedValue(): void
    {
        \Parity\assertType($this->invoiceQuery()->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType($this->invoiceQuery()->all(), \parity_expected_1d2376a17f7fd046d351());
        \Parity\assertType($this->invoiceQuery()->asArray()->one(), \parity_expected_232678477d739d948205());
        \Parity\assertType($this->invoiceQuery()->where(['id' => 1])->asArray()->all(), \parity_expected_03edae2e364547df8b19());
        \Parity\assertType($this->invoiceQuery()->asArray(false)->one(), \parity_expected_6e2d2921b8c12eaf4ed1());

        foreach ($this->invoiceQuery()->batch(10) as $invoices) {
            \Parity\assertType($invoices, \parity_expected_1d2376a17f7fd046d351());
        }

        foreach ($this->invoiceQuery()->each() as $invoice) {
            \Parity\assertType($invoice, \parity_expected_4e7debefd5fcd892f188());
        }
    }

    public function testReturnInvoiceWhenQueryIsScopeResult(InvoiceQuery $query): void
    {
        \Parity\assertType($query->paid()->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType($query->paid()->all(), \parity_expected_1d2376a17f7fd046d351());
        \Parity\assertType(Invoice::find()->paid()->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType($this->creditInvoiceQuery->refunded()->paid()->where(['id' => 1])->all(), \parity_expected_1d2376a17f7fd046d351());
        \Parity\assertType($query->paid()->asArray()->one(), \parity_expected_232678477d739d948205());
        \Parity\assertType($query->paid()->asArray()->all(), \parity_expected_03edae2e364547df8b19());
        \Parity\assertType($query->paid()->asArray(false)->one(), \parity_expected_6e2d2921b8c12eaf4ed1());

        foreach ($query->paid()->batch() as $invoices) {
            \Parity\assertType($invoices, \parity_expected_1d2376a17f7fd046d351());
        }

        foreach (Invoice::find()->paid()->each() as $invoice) {
            \Parity\assertType($invoice, \parity_expected_4e7debefd5fcd892f188());
        }
    }

    public function testReturnInvoiceWhenQueryIsTypedParameter(InvoiceQuery $query): void
    {
        \Parity\assertType($query->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType($query->all(), \parity_expected_1d2376a17f7fd046d351());
        \Parity\assertType($query->where(['id' => 1])->andWhere(['number' => 'A'])->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType($query->asArray(), \parity_expected_d7472ecec747be6efef7());
        \Parity\assertType($query->asArray()->one(), \parity_expected_232678477d739d948205());
        \Parity\assertType($query->orderBy('id')->asArray()->all(), \parity_expected_03edae2e364547df8b19());
        \Parity\assertType($query->asArray(false)->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType($query->asArray((bool) $query->count())->one(), \parity_expected_a4aca3c643b7bd6362f8());

        foreach ($query->batch() as $invoices) {
            \Parity\assertType($invoices, \parity_expected_1d2376a17f7fd046d351());
        }

        foreach ($query->each() as $invoice) {
            \Parity\assertType($invoice, \parity_expected_4e7debefd5fcd892f188());
        }
    }

    public function testReturnInvoiceWhenQueryIsTypedProperty(): void
    {
        \Parity\assertType($this->invoiceQuery->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType($this->invoiceQuery->all(), \parity_expected_1d2376a17f7fd046d351());
        \Parity\assertType($this->creditInvoiceQuery->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType($this->invoiceQuery->asArray()->one(), \parity_expected_232678477d739d948205());
        \Parity\assertType($this->creditInvoiceQuery->asArray()->all(), \parity_expected_03edae2e364547df8b19());
        \Parity\assertType($this->invoiceQuery->asArray(false)->where(['id' => 1])->one(), \parity_expected_6e2d2921b8c12eaf4ed1());

        foreach ($this->invoiceQuery->batch() as $invoices) {
            \Parity\assertType($invoices, \parity_expected_1d2376a17f7fd046d351());
        }

        foreach ($this->creditInvoiceQuery->each() as $invoice) {
            \Parity\assertType($invoice, \parity_expected_4e7debefd5fcd892f188());
        }
    }

    private function invoiceQuery(): InvoiceQuery
    {
        return $this->invoiceQuery;
    }
}
