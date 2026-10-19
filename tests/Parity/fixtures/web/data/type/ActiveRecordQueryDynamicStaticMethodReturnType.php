<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveQuery;
use yii2\extensions\phpstan\tests\support\stub\{
    ActiveRecordQueryFactory,
    Comment,
    CreditInvoice,
    Invoice,
    MyActiveRecord,
    Post,
    PriorityTicket,
    Shipment,
    Ticket,
    Voucher,
};

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for the query model inference of {@see Invoice::find()} and other Active Record static methods
 * returning a non-generic custom query class.
 *
 * Covers the Gii-generated {@see Invoice} and {@see \yii2\extensions\phpstan\tests\support\stub\InvoiceQuery} pair, a
 * query class without overrides, a query bound to its model, subclasses, a `: self` scope on a subclass query, whose
 * rows come from the query class's own `one()` and `all()` overrides, every {@see ActiveQuery} method whose type
 * depends on the row type, and the calls that must stay unchanged.
 */
final class ActiveRecordQueryDynamicStaticMethodReturnType
{
    public function testKeepBoundQueryWhenFindOnBoundModel(): void
    {
        \Parity\assertType(Ticket::find(), \parity_expected_9b65068e58823b899617());
        \Parity\assertType(Ticket::find()->open()->one(), \parity_expected_5874852853ab05d2b5ea());
        \Parity\assertType(Comment::find(), \parity_expected_ba7e240c8b4191a020c1());
    }

    public function testKeepGenericQueryWhenModelHasNoCustomOrGenericQueryClass(): void
    {
        \Parity\assertType(MyActiveRecord::find(), \parity_expected_8f8c179ed541f0ecf701());
        \Parity\assertType(Post::find(), \parity_expected_9ad2aed4a9691f1670c7());
    }

    public function testKeepInferredQueryWhenCacheAndExecutionMethodIsCalled(): void
    {
        \Parity\assertType(Invoice::find()->cache(), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->noCache(), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->emulateExecution(), \parity_expected_589914032359d26f0301());
    }

    public function testKeepInferredQueryWhenJoinMethodIsCalled(): void
    {
        \Parity\assertType(Invoice::find()->join('LEFT JOIN', 'item'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->innerJoin('item'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->leftJoin('item'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->rightJoin('item'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->joinWith('comments'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->innerJoinWith('comments'), \parity_expected_589914032359d26f0301());
    }

    public function testKeepInferredQueryWhenLimitOffsetIndexMethodIsCalled(): void
    {
        \Parity\assertType(Invoice::find()->limit(10), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->offset(10), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->indexBy('id'), \parity_expected_589914032359d26f0301());
    }

    public function testKeepInferredQueryWhenOrderGroupHavingMethodIsCalled(): void
    {
        \Parity\assertType(Invoice::find()->orderBy('id'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->addOrderBy('id'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->groupBy('id'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->addGroupBy('id'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->having(['id' => 1]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->andHaving(['id' => 1]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->orHaving(['id' => 1]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->filterHaving(['id' => 1]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->andFilterHaving(['id' => 1]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->orFilterHaving(['id' => 1]), \parity_expected_589914032359d26f0301());
    }

    public function testKeepInferredQueryWhenRelationalMethodIsCalled(): void
    {
        \Parity\assertType(Invoice::find()->with('comments'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->via('comments'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->viaTable('invoice_comment', ['invoice_id' => 'id']), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->inverseOf('invoice'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->onCondition(['id' => 1]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->andOnCondition(['id' => 1]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->orOnCondition(['id' => 1]), \parity_expected_589914032359d26f0301());
    }

    public function testKeepInferredQueryWhenSelectAndFromMethodIsCalled(): void
    {
        \Parity\assertType(Invoice::find()->select('id'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->addSelect('id'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->distinct(), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->from('invoice'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->alias('i'), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->params([]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->addParams([]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->withQuery(Invoice::find(), 'w'), \parity_expected_589914032359d26f0301());
    }

    public function testKeepInferredQueryWhenUnionMethodIsCalled(): void
    {
        \Parity\assertType(Invoice::find()->union(Invoice::find()), \parity_expected_589914032359d26f0301());
    }

    public function testKeepInferredQueryWhenWhereFilterMethodIsCalled(): void
    {
        \Parity\assertType(Invoice::find()->where(['id' => 1]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->andWhere(['id' => 1]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->orWhere(['id' => 1]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->filterWhere(['id' => 1]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->andFilterWhere(['id' => 1]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->orFilterWhere(['id' => 1]), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->andFilterCompare('id', '>1'), \parity_expected_589914032359d26f0301());
    }

    public function testKeepNativeTypeWhenStaticCallIsNotInferable(): void
    {
        \Parity\assertType(Voucher::code(), \parity_expected_473287f8298dba7163a8());
        \Parity\assertType(Voucher::find(), \parity_expected_103cc6c0d343f39c5f4d());
        \Parity\assertType(Voucher::findEither(true), \parity_expected_1bc77dde902e9bbe641c());
        \Parity\assertType(ActiveRecordQueryFactory::invoices(), \parity_expected_799261f07fad4d570694());
        \Parity\assertType(Invoice::find(...), \parity_expected_306acb7338c924482915());
    }

    public function testKeepRowIndependentTypesWhenQueryIsInferred(): void
    {
        \Parity\assertType(Invoice::find()->populate([]), \parity_expected_dbe42cc09c16704aa3d6());
        \Parity\assertType(Invoice::find()->createCommand(), \parity_expected_9dd36807f8e63e0919fd());
        \Parity\assertType(Invoice::find()->count(), \parity_expected_c8897e14789605459e8e());
        \Parity\assertType(Invoice::find()->exists(), \parity_expected_b760f44fa5965c2474a3());
        \Parity\assertType(Invoice::find()->scalar(), \parity_expected_01905e462251a4b4d202());
        \Parity\assertType(Invoice::find()->column(), \parity_expected_dbe42cc09c16704aa3d6());
        \Parity\assertType(Invoice::find()->getBehavior('timestamp'), \parity_expected_376095e9be0190c61852());
    }

    public function testKeepStaticQueryMethodsUnchangedWhenModelHasCustomQueryClass(): void
    {
        \Parity\assertType(Invoice::findOne(1), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType(Invoice::findAll([1, 2]), \parity_expected_1d2376a17f7fd046d351());
        \Parity\assertType(Invoice::findBySql('SELECT * FROM invoice'), \parity_expected_2f6b61350746e975d201());
        \Parity\assertType((new Invoice())->hasMany(Comment::class, ['invoice_id' => 'id']), \parity_expected_f19fca7fefc10fc40cec());
        \Parity\assertType((new Invoice())->getComments(), \parity_expected_e4c5ad1fcf018208a569());
    }

    public function testReturnInvoiceRowShapeWhenAsArrayOnGiiGeneratedModel(): void
    {
        \Parity\assertType(Invoice::find()->asArray(), \parity_expected_d7472ecec747be6efef7());
        \Parity\assertType(Invoice::find()->where(['id' => 1])->asArray()->one(), \parity_expected_232678477d739d948205());
        \Parity\assertType(Invoice::find()->asArray()->all(), \parity_expected_03edae2e364547df8b19());
        \Parity\assertType(Invoice::find()->asArray(false)->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
    }

    public function testReturnInvoiceWhenBatchOrEachOnGiiGeneratedModel(): void
    {
        foreach (Invoice::find()->where(['id' => 1])->batch(10) as $invoices) {
            \Parity\assertType($invoices, \parity_expected_1d2376a17f7fd046d351());
        }

        foreach (Invoice::find()->each() as $invoice) {
            \Parity\assertType($invoice, \parity_expected_4e7debefd5fcd892f188());
        }
    }

    /**
     * @param class-string<Invoice> $modelClass
     */
    public function testReturnInvoiceWhenFindOnClassStringOrObject(string $modelClass, Invoice $invoice): void
    {
        \Parity\assertType($modelClass::find()->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType($modelClass::find()->all(), \parity_expected_1d2376a17f7fd046d351());
        \Parity\assertType($invoice::find()->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
    }

    public function testReturnInvoiceWhenFindOnGiiGeneratedModel(): void
    {
        \Parity\assertType(Invoice::find(), \parity_expected_589914032359d26f0301());
        \Parity\assertType(Invoice::find()->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType(Invoice::find()->all(), \parity_expected_1d2376a17f7fd046d351());
        \Parity\assertType(\yii2\extensions\phpstan\tests\support\stub\Invoice::find()->where(['id' => 1])->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType(Invoice::find()->where(['id' => 1])->andWhere(['number' => 'A'])->orderBy('id')->limit(5)->with('comments')
                ->indexBy('id')->all(), \parity_expected_1d2376a17f7fd046d351());
    }

    public function testReturnInvoiceWhenSelfScopeFollowsFindOnSubclass(): void
    {
        \Parity\assertType(CreditInvoice::find()->paid(), \parity_expected_799261f07fad4d570694());
        \Parity\assertType(CreditInvoice::find()->paid()->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
        \Parity\assertType(CreditInvoice::find()->paid()->all(), \parity_expected_1d2376a17f7fd046d351());
    }

    public function testReturnShipmentWhenFindOnModelWithBareQueryClass(): void
    {
        \Parity\assertType(Shipment::find(), \parity_expected_d280ad5e6bebeed41853());
        \Parity\assertType(Shipment::find()->one(), \parity_expected_746f3b420358f41bc942());
        \Parity\assertType(Shipment::find()->with('items')->indexBy('id')->all(), \parity_expected_abfd09a6003fe8c78411());
        \Parity\assertType(Shipment::find()->pending()->where(['id' => 1])->shipped()->one(), \parity_expected_746f3b420358f41bc942());
        \Parity\assertType(Shipment::find()->pending()->asArray()->one(), \parity_expected_720df6b908291c7b8879());

        foreach (Shipment::find()->batch() as $shipments) {
            \Parity\assertType($shipments, \parity_expected_abfd09a6003fe8c78411());
        }

        foreach (Shipment::find()->each() as $shipment) {
            \Parity\assertType($shipment, \parity_expected_c1ac08f476762d86ddbf());
        }
    }

    public function testReturnSubclassWhenFindOnSubclassOfModel(): void
    {
        \Parity\assertType(CreditInvoice::find(), \parity_expected_94f4836e6f007daebcda());
        \Parity\assertType(CreditInvoice::find()->one(), \parity_expected_a21e4b4fec748d68ad54());
        \Parity\assertType(PriorityTicket::find(), \parity_expected_cee28fd704db632653bc());
        \Parity\assertType(PriorityTicket::find()->open()->one(), \parity_expected_8629cd561ae5c7d2d9ab());
        \Parity\assertType(PriorityTicket::find()->where(['id' => 1])->all(), \parity_expected_d1e5b6a4a903c8814208());

        foreach (PriorityTicket::find()->each() as $ticket) {
            \Parity\assertType($ticket, \parity_expected_25373d3ab6c39ff962ed());
        }
    }

    public function testReturnWideTypeWhenScopeReturnsSelfOrHasNoReturnType(): void
    {
        \Parity\assertType(Shipment::find()->delivered(), \parity_expected_9546505a36c8f1ca7249());
        \Parity\assertType(Shipment::find()->returned(), \parity_expected_3f8fee624f43b2a9d685());
    }
}
