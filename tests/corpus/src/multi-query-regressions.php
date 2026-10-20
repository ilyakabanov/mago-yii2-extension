<?php

declare(strict_types=1);

namespace Corpus\MultiQueryRegressions;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/** @property int $id */
class Entry extends ActiveRecord
{
    /** @return RowSecond<string, static, int> */
    public static function find(): RowSecond
    {
        return new RowSecond(static::class);
    }

    public static function lateOne(): ?static
    {
        return static::find()->one();
    }

    public function thisOne(): ?static
    {
        return $this::find()->one();
    }
}

class SpecialEntry extends Entry {}

/** @property int $id */
class FixedEntry extends ActiveRecord
{
    /** @return RowSecond<string, FixedEntry, int> */
    // The concrete factory intentionally narrows Yii's late-static parent contract.
    // @mago-expect analysis:incompatible-return-type
    public static function find(): RowSecond
    {
        return new RowSecond(self::class);
    }
}

class SpecialFixedEntry extends FixedEntry {}

/**
 * @template TUnused
 * @template TActual of ActiveRecord|array
 * @template TTag
 * @extends ActiveQuery<TActual>
 */
class RowSecond extends ActiveQuery {}

/**
 * @template TActual of ActiveRecord|array
 * @template TUnused
 * @extends ActiveQuery<TActual>
 */
class RowFirst extends ActiveQuery {}

/**
 * @template TActual of ActiveRecord|array
 * @template TUnused
 * @extends RowSecond<TUnused, TActual, int>
 */
class ReorderedRows extends RowSecond {}

/**
 * @template TActual of ActiveRecord|array
 * @template TUnused of ActiveRecord|array
 * @extends ActiveQuery<TActual>
 */
class AmbiguousRows extends ActiveQuery {}

/**
 * @template TKey of array-key
 * @template TActual of ActiveRecord
 * @extends ActiveQuery<TActual>
 */
class RecordOnly extends ActiveQuery {}

/**
 * @template TActual of ActiveRecord|array
 * @template TList of list<TActual>
 * @extends ActiveQuery<TActual>
 */
class DependentRows extends ActiveQuery {}

/** @param DependentRows<Entry, list<Entry>> $query */
function dependentBounds(DependentRows $query): void
{
    \Mago\confirm($query->asArray(), "yii\db\ActiveQuery<array{'id': int}>");
    \Mago\confirm($query->asArray()->one(), "array{'id': int}|null");
}

/**
 * @template TKey of array-key
 * @template TActual of ActiveRecord|array
 * @extends ActiveQuery<TActual>
 */
class PreciseRows extends ActiveQuery
{
    /** @return TActual */
    public function one($db = null)
    {
        throw new \LogicException('No database is used in this fixture.');
    }

    /** @return list<TActual> */
    public function all($db = null): array
    {
        return [];
    }
}

/**
 * @template TUnused of Entry
 * @template TAlsoUnused of Entry
 * @extends ActiveQuery<Entry>
 */
class FixedRows extends ActiveQuery {}

/**
 * @template TUnused of ActiveRecord
 * @template TAlsoUnused of ActiveRecord
 * @extends ActiveQuery<never>
 */
class EmptyRows extends ActiveQuery {}

/**
 * @template TKey of array-key
 * @template TActual of ActiveRecord
 * @extends ActiveQuery<TActual&Entry>
 */
class NarrowRows extends ActiveQuery {}

/**
 * @template TKey of array-key
 * @template TActual of ActiveRecord
 * @extends ActiveQuery<TActual|Entry>
 */
class UnionRows extends ActiveQuery {}

/**
 * @template TKey of array-key
 * @template TActual of ActiveRecord
 * @extends ActiveQuery<array{model: TActual}>
 */
class WrappedRows extends ActiveQuery {}

/**
 * @param FixedRows<Entry, Entry> $fixed
 * @param EmptyRows<Entry, Entry> $empty
 * @param NarrowRows<string, ActiveRecord> $narrow
 * @param UnionRows<string, SpecialEntry> $union
 * @param WrappedRows<string, Entry> $wrapped
 * @param AmbiguousRows<Entry, Entry> $ambiguous
 */
function unsupportedBindings(
    FixedRows $fixed,
    EmptyRows $empty,
    NarrowRows $narrow,
    UnionRows $union,
    WrappedRows $wrapped,
    AmbiguousRows $ambiguous,
): void {
    \Mago\confirm($fixed->one(), 'Corpus\MultiQueryRegressions\Entry|null');
    \Mago\confirm($fixed->asArray()->one(), 'Corpus\MultiQueryRegressions\Entry|null');
    \Mago\confirm($empty->one(), 'null');
    \Mago\confirm($empty->asArray()->one(), 'null');
    \Mago\confirm($narrow->one(), '(yii\db\ActiveRecord&Corpus\MultiQueryRegressions\Entry)|null');
    \Mago\confirm($narrow->asArray()->one(), '(yii\db\ActiveRecord&Corpus\MultiQueryRegressions\Entry)|null');
    \Mago\confirm($union->one(), 'Corpus\MultiQueryRegressions\Entry|Corpus\MultiQueryRegressions\SpecialEntry|null');
    \Mago\confirm($wrapped->one(), "array{'model': Corpus\MultiQueryRegressions\Entry}|null");
    \Mago\confirm($wrapped->asArray()->one(), "array{'model': yii\db\ActiveRecord}|null");
    \Mago\confirm($union->asArray()->one(), 'Corpus\MultiQueryRegressions\Entry|null|yii\db\ActiveRecord');
    // Equal template bounds do not provide a unique symbolic containment proof in the host.
    \Mago\confirm($ambiguous->asArray()->one(), 'Corpus\MultiQueryRegressions\Entry|array<string, mixed>|null');
}

/**
 * @param RowSecond<Entry, Entry, 'tag'> $query
 * @param RowFirst<Entry, Entry> $reversed
 * @param ReorderedRows<Entry, string> $inherited
 * @param RowSecond<Entry, array{id: int}, 'tag'> $arrays
 * @param RecordOnly<string, Entry> $records
 * @param PreciseRows<string, Entry> $precise
 */
function multiRows(
    RowSecond $query,
    RowFirst $reversed,
    ReorderedRows $inherited,
    RowSecond $arrays,
    RecordOnly $records,
    PreciseRows $precise,
    bool $enabled,
): void {
    \Mago\confirm($query->one(), 'Corpus\MultiQueryRegressions\Entry|null');
    \Mago\confirm($query->all(), 'array<array-key, Corpus\MultiQueryRegressions\Entry>');
    \Mago\confirm(
        $query->asArray(),
        "Corpus\MultiQueryRegressions\RowSecond<Corpus\MultiQueryRegressions\Entry, array{'id': int}, string('tag')>",
    );
    \Mago\confirm($query->asArray()->one(), "array{'id': int}|null");
    \Mago\confirm($query->asArray(true)->where([])->all(), "array<array-key, array{'id': int}>");
    \Mago\confirm(
        $query->asArray(false),
        "Corpus\MultiQueryRegressions\RowSecond<Corpus\MultiQueryRegressions\Entry, Corpus\MultiQueryRegressions\Entry, string('tag')>",
    );
    \Mago\confirm($query->asArray($enabled)->one(), "Corpus\MultiQueryRegressions\Entry|array{'id': int}|null");
    \Mago\confirm(
        $query->asArray(...[false]),
        "Corpus\MultiQueryRegressions\RowSecond<Corpus\MultiQueryRegressions\Entry, Corpus\MultiQueryRegressions\Entry, string('tag')>",
    );
    \Mago\confirm($query->asArray(...[true])->one(), 'array<string, mixed>|null');
    \Mago\confirm($query->asArray(...[$enabled])->one(), 'array<array-key, mixed>|null|yii\db\ActiveRecord');
    \Mago\confirm(
        $reversed->asArray(),
        "Corpus\MultiQueryRegressions\RowFirst<array{'id': int}, Corpus\MultiQueryRegressions\Entry>",
    );
    \Mago\confirm($inherited->asArray(), "Corpus\MultiQueryRegressions\ReorderedRows<array{'id': int}, string>");
    \Mago\confirm($inherited->asArray()->one(), "array{'id': int}|null");
    \Mago\confirm(
        $arrays->asArray(),
        "Corpus\MultiQueryRegressions\RowSecond<Corpus\MultiQueryRegressions\Entry, array{'id': int}, string('tag')>",
    );
    \Mago\confirm(
        $query->batch(),
        'yii\db\BatchQueryResult<int, array<array-key, Corpus\MultiQueryRegressions\Entry>>',
    );
    \Mago\confirm($query->each(), 'yii\db\BatchQueryResult<array-key, Corpus\MultiQueryRegressions\Entry>');
    \Mago\confirm($query->asArray()->batch(), "yii\db\BatchQueryResult<int, array<array-key, array{'id': int}>>");
    \Mago\confirm($query->asArray()->each(), "yii\db\BatchQueryResult<array-key, array{'id': int}>");
    \Mago\confirm($records->asArray(), "yii\db\ActiveQuery<array{'id': int}>");
    \Mago\confirm($records->asArray($enabled)->one(), "Corpus\MultiQueryRegressions\Entry|array{'id': int}|null");
    \Mago\confirm($precise->one(), 'Corpus\MultiQueryRegressions\Entry');
    \Mago\confirm($precise->all(), 'list<Corpus\MultiQueryRegressions\Entry>');
    \Mago\confirm($precise->asArray()->one(), "array{'id': int}");
    \Mago\confirm($precise->asArray()->all(), "list<array{'id': int}>");
    $row = $query->asArray()->one();
    if ($row !== null) {
        acceptsInt($row['id']);
        // @mago-expect analysis:invalid-argument
        acceptsText($row['id']);
    }
}

function acceptsInt(int $value): void {}

function acceptsText(string $value): void {}

/** @param class-string<Entry> $class */
function staticQueries(string $class): void
{
    \Mago\confirm(
        SpecialFixedEntry::find(),
        'Corpus\MultiQueryRegressions\RowSecond<string, Corpus\MultiQueryRegressions\FixedEntry, int>',
    );
    \Mago\confirm(SpecialFixedEntry::find()->one(), 'Corpus\MultiQueryRegressions\FixedEntry|null');
    \Mago\confirm(
        $class::find(),
        'Corpus\MultiQueryRegressions\RowSecond<string, Corpus\MultiQueryRegressions\Entry, int>',
    );
    \Mago\confirm(
        Entry::find(),
        'Corpus\MultiQueryRegressions\RowSecond<string, Corpus\MultiQueryRegressions\Entry, int>',
    );
    \Mago\confirm(
        SpecialEntry::find(),
        'Corpus\MultiQueryRegressions\RowSecond<string, Corpus\MultiQueryRegressions\SpecialEntry, int>',
    );
    \Mago\confirm(SpecialEntry::find()->one(), 'Corpus\MultiQueryRegressions\SpecialEntry|null');
    \Mago\confirm(Entry::find()->asArray()->one(), "array{'id': int}|null");
}
