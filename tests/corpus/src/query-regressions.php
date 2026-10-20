<?php

declare(strict_types=1);

namespace Corpus\QueryRegressions;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/** @property int $id */
class Receipt extends ActiveRecord
{
    public static function find(): ReceiptQuery
    {
        return new ReceiptQuery(static::class);
    }

    public static function lateStaticOne(): ?static
    {
        return static::find()->one();
    }

    public function lateThisOne(): ?static
    {
        return $this::find()->one();
    }
}

class ReceiptQuery extends ActiveQuery
{
    /** @return Receipt|array<string, mixed>|null */
    public function one($db = null)
    {
        throw new \LogicException('No database is used in this fixture.');
    }

    /** @return array<array-key, Receipt|array<string, mixed>> */
    public function all($db = null)
    {
        return [];
    }
}

class SpecialReceipt extends Receipt {}

/**
 * @template TKey of array-key
 * @template TModel of ActiveRecord
 * @extends ActiveQuery<TModel>
 */
class KeyedQuery extends ActiveQuery {}

/**
 * @template TRow of ActiveRecord|array
 * @extends ActiveQuery<TRow>
 */
class RowQuery extends ActiveQuery {}

/**
 * @template T of ActiveRecord
 * @extends ActiveQuery<T>
 */
class StrictQuery extends ActiveQuery
{
    /** @return T */
    public function one($db = null)
    {
        throw new \LogicException('No database is used in this fixture.');
    }

    /** @return list<T> */
    public function all($db = null): array
    {
        return [];
    }
}

/**
 * @template T of ActiveRecord
 * @extends StrictQuery<T>
 */
class InheritedStrictQuery extends StrictQuery {}

/**
 * @template T of ActiveRecord
 * @extends ActiveQuery<never>
 */
class NeverQuery extends ActiveQuery {}

/**
 * @template T of ActiveRecord
 * @extends ActiveQuery<T&Receipt>
 */
class NarrowQuery extends ActiveQuery {}

/**
 * @template T of Receipt
 * @extends ActiveQuery<Receipt>
 */
class FixedBoundQuery extends ActiveQuery {}

class GenericReceipt extends ActiveRecord
{
    /** @return RowQuery<static> */
    public static function find(): RowQuery
    {
        return new RowQuery(static::class);
    }

    public static function lateStaticOne(): ?static
    {
        return static::find()->one();
    }

    public function lateThisOne(): ?static
    {
        return $this::find()->one();
    }
}

/**
 * @template TKey of array-key
 * @extends ActiveQuery<Receipt>
 */
class KeyOnlyQuery extends ActiveQuery {}

/**
 * @template TUnused of ActiveRecord
 * @extends ActiveQuery<Receipt>
 */
class UnusedModelQuery extends ActiveQuery {}

/** @extends ActiveQuery<Receipt> */
class PreciseQuery extends ActiveQuery
{
    public function one($db = null): Receipt
    {
        throw new \LogicException('No database is used in this fixture.');
    }

    /** @return list<Receipt> */
    public function all($db = null): array
    {
        return [];
    }
}

final class LookupReceipt extends ActiveRecord
{
    public static function find(): PreciseLookupQuery
    {
        return new PreciseLookupQuery(static::class);
    }

    public static function findOne($condition): self
    {
        return new self();
    }

    /** @return list<self> */
    public static function findAll($condition): array
    {
        return [];
    }
}

/** @extends ActiveQuery<LookupReceipt> */
class PreciseLookupQuery extends ActiveQuery
{
    public function one($db = null): LookupReceipt
    {
        return new LookupReceipt();
    }

    /** @return list<LookupReceipt> */
    public function all($db = null): array
    {
        return [];
    }
}

/** @param class-string<Receipt> $modelClass */
function classStringQuery(string $modelClass): void
{
    \Mago\confirm($modelClass::find()->one(), 'Corpus\QueryRegressions\Receipt|null');
    \Mago\confirm($modelClass::find()->all(), 'array<array-key, Corpus\QueryRegressions\Receipt>');
    \Mago\confirm(SpecialReceipt::find()->one(), 'Corpus\QueryRegressions\SpecialReceipt|null');
    $literalClass = Receipt::class;
    \Mago\confirm($literalClass::find()->one(), 'Corpus\QueryRegressions\Receipt|null');
    \Mago\confirm(GenericReceipt::find()->one(), 'Corpus\QueryRegressions\GenericReceipt|null');
    $genericClass = GenericReceipt::class;
    \Mago\confirm($genericClass::find()->one(), 'Corpus\QueryRegressions\GenericReceipt|null');
}

/**
 * @param RowQuery<Receipt> $rows
 * @param KeyOnlyQuery<string> $keys
 * @param UnusedModelQuery<SpecialReceipt> $unused
 */
function singleTemplateQueryRows(RowQuery $rows, KeyOnlyQuery $keys, UnusedModelQuery $unused): void
{
    \Mago\confirm($rows->asArray(), "Corpus\QueryRegressions\RowQuery<array{'id': int}>");
    \Mago\confirm($rows->asArray()->one(), "array{'id': int}|null");
    \Mago\confirm($rows->asArray(false), 'Corpus\QueryRegressions\RowQuery<Corpus\QueryRegressions\Receipt>');
    \Mago\confirm($keys->one(), 'Corpus\QueryRegressions\Receipt|null');
    \Mago\confirm($unused->one(), 'Corpus\QueryRegressions\Receipt|null');
    $keys->asArray();
    $unused->asArray();
}

/**
 * @param StrictQuery<ActiveRecord> $unbound
 * @param StrictQuery<Receipt> $concrete
 * @param InheritedStrictQuery<ActiveRecord> $inherited
 * @param InheritedStrictQuery<Receipt> $inheritedConcrete
 */
function genericQueryOverrides(
    StrictQuery $unbound,
    StrictQuery $concrete,
    InheritedStrictQuery $inherited,
    InheritedStrictQuery $inheritedConcrete,
): void {
    \Mago\confirm($unbound->one(), 'yii\db\ActiveRecord');
    \Mago\confirm($unbound->all(), 'list<yii\db\ActiveRecord>');
    \Mago\confirm($concrete->one(), 'Corpus\QueryRegressions\Receipt');
    \Mago\confirm($concrete->all(), 'list<Corpus\QueryRegressions\Receipt>');
    \Mago\confirm($inherited->one(), 'yii\db\ActiveRecord');
    \Mago\confirm($inherited->all(), 'list<yii\db\ActiveRecord>');
    \Mago\confirm($inheritedConcrete->one(), 'Corpus\QueryRegressions\Receipt');
    \Mago\confirm($inheritedConcrete->all(), 'list<Corpus\QueryRegressions\Receipt>');
}

/**
 * @param NeverQuery<Receipt> $never
 * @param NarrowQuery<ActiveRecord> $narrow
 * @param FixedBoundQuery<Receipt> $fixed
 * @param UnusedModelQuery<Receipt> $coincident
 */
function symbolicBindingControls(
    NeverQuery $never,
    NarrowQuery $narrow,
    FixedBoundQuery $fixed,
    UnusedModelQuery $coincident,
): void {
    \Mago\confirm($never->one(), 'null');
    \Mago\confirm($narrow->one(), '(yii\db\ActiveRecord&Corpus\QueryRegressions\Receipt)|null');
    \Mago\confirm($fixed->one(), 'Corpus\QueryRegressions\Receipt|null');
    \Mago\confirm($coincident->one(), 'Corpus\QueryRegressions\Receipt|null');
    // These unproven row bindings must keep the native asArray contract, even when concrete arguments coincide.
    \Mago\confirm($never->asArray()->one(), 'null');
    \Mago\confirm($fixed->asArray()->one(), 'Corpus\QueryRegressions\Receipt|null');
    \Mago\confirm($coincident->asArray()->one(), 'Corpus\QueryRegressions\Receipt|null');
}

function acceptsReceipt(Receipt $receipt): void {}

function acceptsText(string $value): void {}

/** @param KeyedQuery<string, Receipt> $query */
function genericQueryRows(KeyedQuery $query): void
{
    \Mago\confirm($query->one(), 'Corpus\QueryRegressions\Receipt|null');
    \Mago\confirm($query->all(), 'array<array-key, Corpus\QueryRegressions\Receipt>');
    $receipt = $query->one();
    if ($receipt !== null) {
        acceptsReceipt($receipt);
        // @mago-expect analysis:invalid-argument
        acceptsText($receipt);
    }
    // Only a proven single row template is rewritten; multi-template asArray keeps native inference.
    \Mago\confirm($query->asArray()->one(), 'null|yii\db\ActiveRecord');
}

/** @param PreciseQuery&ActiveQuery<Receipt> $bound */
function preciseQueryOverrides(PreciseQuery $bound): void
{
    \Mago\confirm($bound->one(), 'Corpus\QueryRegressions\Receipt');
    \Mago\confirm($bound->all(), 'list<Corpus\QueryRegressions\Receipt>');
    \Mago\confirm(LookupReceipt::find()->one(), 'Corpus\QueryRegressions\LookupReceipt');
    \Mago\confirm(LookupReceipt::find()->all(), 'list<Corpus\QueryRegressions\LookupReceipt>');
    \Mago\confirm(LookupReceipt::findOne(1), 'Corpus\QueryRegressions\LookupReceipt');
    \Mago\confirm(LookupReceipt::findAll([1]), 'list<Corpus\QueryRegressions\LookupReceipt>');
}
