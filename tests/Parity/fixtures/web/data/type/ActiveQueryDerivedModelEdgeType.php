<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveQuery;
use yii2\extensions\phpstan\tests\support\stub\{
    CommentQuery,
    CreditInvoiceQuery,
    DerivedModelBaseRecordQuery,
    DerivedModelDocQuery,
    DerivedModelFalseAllQuery,
    DerivedModelFalseElementQuery,
    DerivedModelFalseOneQuery,
    DerivedModelNativeQuery,
    DerivedModelNonRecordQuery,
    DerivedModelNullElementQuery,
    DerivedModelNullableAllQuery,
    DerivedModelObjectAllQuery,
    DerivedModelObjectOneQuery,
    DerivedModelSplitQuery,
    DerivedModelTwoModelQuery,
    DerivedModelUntypedAllQuery,
    InvoiceQuery,
    ShipmentQuery,
};

use function is_array;
use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for the query classes from which no model is derived or whose `one()` and `all()` overrides
 * are already tight, and for union receivers.
 */
final class ActiveQueryDerivedModelEdgeType
{
    public function testKeepNativeTypeWhenOverridesDeclareBranchesActiveQueryCannotReturn(
        DerivedModelFalseAllQuery $falseAllQuery,
        DerivedModelFalseElementQuery $falseElementQuery,
        DerivedModelFalseOneQuery $falseOneQuery,
        DerivedModelNullableAllQuery $nullableAllQuery,
        DerivedModelNullElementQuery $nullElementQuery,
        DerivedModelObjectAllQuery $objectAllQuery,
        DerivedModelObjectOneQuery $objectOneQuery,
        DerivedModelUntypedAllQuery $untypedAllQuery,
    ): void {
        \Parity\assertType($falseAllQuery->all(), \parity_expected_2362d034042a194347d7());
        \Parity\assertType($falseAllQuery->one(), \parity_expected_4049cdb043f0b7dc6d17());
        \Parity\assertType($falseAllQuery->asArray(), \parity_expected_bf9b6c8b6c559e197a05());
        \Parity\assertType($falseOneQuery->one(), \parity_expected_4836012ca8c64ac933dd());
        \Parity\assertType($falseOneQuery->all(), \parity_expected_1d2376a17f7fd046d351());
        \Parity\assertType($falseOneQuery->asArray(), \parity_expected_bf9b6c8b6c559e197a05());
        \Parity\assertType($nullableAllQuery->all(), \parity_expected_cae7e18ed515040f5eac());
        \Parity\assertType($falseElementQuery->all(), \parity_expected_7bc9129096dc8555f98f());
        \Parity\assertType($falseElementQuery->one(), \parity_expected_4049cdb043f0b7dc6d17());
        \Parity\assertType($nullElementQuery->all(), \parity_expected_e56c64e1bf2fbfc75fff());
        \Parity\assertType($objectAllQuery->all(), \parity_expected_615286089a850a047508());
        \Parity\assertType($objectAllQuery->one(), \parity_expected_4049cdb043f0b7dc6d17());
        \Parity\assertType($objectOneQuery->one(), \parity_expected_17fb12e2f84a62ebd366());
        \Parity\assertType($untypedAllQuery->all(), \parity_expected_dbe42cc09c16704aa3d6());
        \Parity\assertType($untypedAllQuery->one(), \parity_expected_4049cdb043f0b7dc6d17());
    }

    public function testKeepNativeTypeWhenOverridesNameNoSingleModel(
        DerivedModelTwoModelQuery $twoModelQuery,
        DerivedModelSplitQuery $splitQuery,
        DerivedModelBaseRecordQuery $baseRecordQuery,
        DerivedModelNonRecordQuery $nonRecordQuery,
    ): void {
        \Parity\assertType($twoModelQuery->one(), \parity_expected_7174c089a4a7725ec48c());
        \Parity\assertType(is_array($twoModelQuery->all()[0] ?? null), \parity_expected_b760f44fa5965c2474a3());
        \Parity\assertType($twoModelQuery->asArray(), \parity_expected_bf9b6c8b6c559e197a05());
        \Parity\assertType($splitQuery->one(), \parity_expected_4049cdb043f0b7dc6d17());
        \Parity\assertType($splitQuery->all(), \parity_expected_abfd09a6003fe8c78411());
        \Parity\assertType($baseRecordQuery->one(), \parity_expected_f2c7598a59ec6680155c());
        \Parity\assertType($baseRecordQuery->all(), \parity_expected_289091123395dbda2ec9());
        \Parity\assertType($nonRecordQuery->one(), \parity_expected_cc50df8cf357a0dbc44c());
        \Parity\assertType($nonRecordQuery->asArray(), \parity_expected_bf9b6c8b6c559e197a05());
    }

    public function testKeepNativeTypeWhenReceiverIsUnion(InvoiceQuery|ShipmentQuery $query): void
    {
        \Parity\assertType($query->one(), \parity_expected_f2c7598a59ec6680155c());
    }

    public function testKeepOwnAnswerOrBetterWhenOverridesAreTight(
        DerivedModelNativeQuery $nativeQuery,
        DerivedModelDocQuery $docQuery,
    ): void {
        \Parity\assertType($nativeQuery->one(), \parity_expected_b36eeeebb109ce8e0d9c());
        \Parity\assertType($nativeQuery->all(), \parity_expected_9fafb20a649911abe718());
        \Parity\assertType($nativeQuery->where(['id' => 1])->one(), \parity_expected_b36eeeebb109ce8e0d9c());
        \Parity\assertType($nativeQuery->asArray(false)->all(), \parity_expected_9fafb20a649911abe718());
        \Parity\assertType($nativeQuery->asArray()->one(), \parity_expected_d363195c51c199ffabf2());
        \Parity\assertType($docQuery->one(), \parity_expected_63b2edeb903808e21826());
        \Parity\assertType($docQuery->where(['id' => 1])->all(), \parity_expected_00250ebb588b44018856());

        foreach ($docQuery->each() as $post) {
            \Parity\assertType($post, \parity_expected_b36eeeebb109ce8e0d9c());
        }
    }

    public function testKeepRawActiveQueryWhenRelationUnionCollapses(ActiveQuery|CommentQuery $query): void
    {
        \Parity\assertType($query, \parity_expected_e4c5ad1fcf018208a569());
        \Parity\assertType(is_array($query->one()), \parity_expected_b760f44fa5965c2474a3());
    }

    public function testReturnInvoiceWhenUnionCollapsesToSingleQueryClass(InvoiceQuery|CreditInvoiceQuery $query): void
    {
        \Parity\assertType($query->one(), \parity_expected_6e2d2921b8c12eaf4ed1());
    }
}
