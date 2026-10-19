<?php

declare(strict_types=1);

namespace Corpus\Queries;

use yii\db\{ActiveQuery, ActiveRecord};

/**
 * @property int $id
 * @property string $number
 * @property-read string $label
 * @property Comment[] $comments
 */
class Invoice extends ActiveRecord
{
    public static function find(): InvoiceQuery
    {
        return new InvoiceQuery(static::class);
    }
}

/**
 * @property int $id
 * @property string $body
 */
class Comment extends ActiveRecord {}

/** @extends ActiveQuery<Invoice|array<string, mixed>> */
class InvoiceQuery extends ActiveQuery
{
    /** @return Invoice|array<string, mixed>|null */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /** @return array<array-key, Invoice|array<string, mixed>> */
    public function all($db = null)
    {
        return parent::all($db);
    }
}

function acceptsInvoice(Invoice $invoice): void {}
function acceptsComment(Comment $comment): void {}
function acceptsInvoiceQuery(InvoiceQuery $query): void {}
function acceptsString(string $value): void {}
function acceptsInt(int $value): void {}

/** @param array<array-key, Invoice> $invoices */
function acceptsInvoices(array $invoices): void {}

/** @param array{id: int, number: string, label?: string, comments?: array<array-key, array<string, mixed>>} $row */
function acceptsInvoiceRow(array $row): void {}

function staticRecordQueries(): void
{
    $query = Invoice::find();
    \Mago\confirm($query, 'Corpus\Queries\InvoiceQuery&yii\db\ActiveQuery<Corpus\Queries\Invoice>');
    acceptsInvoiceQuery($query);
    \Mago\confirm($query->all(), 'array<array-key, Corpus\Queries\Invoice>');
    acceptsInvoices($query->all());
    $one = $query->where(['id' => 1])->orderBy('id')->one();
    if ($one !== null) {
        acceptsInvoice($one);
        // @mago-expect analysis:invalid-argument
        acceptsComment($one);
    }
    // @mago-expect analysis:invalid-argument
    acceptsString($query->all()[0]);
    acceptsInvoices(Invoice::findAll([1, 2]));
    \Mago\confirm(Invoice::findAll([1, 2]), 'array<array-key, Corpus\Queries\Invoice>');
    $found = Invoice::findOne(1);
    if ($found !== null) {
        acceptsInvoice($found);
    }
}

function arrayRowsAndNamedArguments(bool $asArray): void
{
    $row = Invoice::find()->asArray()->one();
    \Mago\confirm($row, "array{'comments'?: array<array-key, array<string, mixed>>, 'id': int, 'label'?: string, 'number': string}|null");
    if ($row !== null) {
        acceptsInvoiceRow($row);
        acceptsInt($row['id']);
        acceptsString($row['number']);
        // @mago-expect analysis:invalid-argument
        acceptsString($row['id']);
    }
    $model = Invoice::find()->asArray(value: false)->one();
    if ($model !== null) {
        acceptsInvoice($model);
    }
    $variable = Invoice::find()->asArray($asArray)->one();
    if ($variable instanceof Invoice) {
        acceptsInvoice($variable);
    } elseif ($variable !== null) {
        acceptsInvoiceRow($variable);
    }
}

function derivedQueries(InvoiceQuery $query): void
{
    \Mago\confirm($query->one(), 'Corpus\Queries\Invoice|null');
    \Mago\confirm($query->all(), 'array<array-key, Corpus\Queries\Invoice>');
    acceptsInvoices($query->all());
    $invoice = $query->one();
    if ($invoice !== null) {
        acceptsInvoice($invoice);
    }
    $row = $query->asArray()->one();
    if ($row !== null) {
        acceptsInvoiceRow($row);
    }
}

/** @param ActiveQuery<Invoice> $query */
function batchesAndRelations(ActiveQuery $query, Invoice $invoice): void
{
    \Mago\confirm($query->batch(10), 'yii\db\BatchQueryResult<int, array<array-key, Corpus\Queries\Invoice>>');
    \Mago\confirm($query->each(10), 'yii\db\BatchQueryResult<array-key, Corpus\Queries\Invoice>');
    acceptsInvoices($query->indexBy('id')->all());
    foreach ($query->batch(10) as $batch) {
        acceptsInvoices($batch);
    }
    foreach ($query->each(10) as $record) {
        acceptsInvoice($record);
    }
    $comment = $invoice->hasOne(Comment::class, ['id' => 'id'])->one();
    if ($comment !== null) {
        acceptsComment($comment);
        // @mago-expect analysis:invalid-argument
        acceptsInvoice($comment);
    }
}
