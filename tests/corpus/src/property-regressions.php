<?php

declare(strict_types=1);

namespace Corpus\PropertyRegressions;

use yii\db\ActiveRecord;

/** @property string $slug */
interface SlugDeclaration {}

/** @property int $revision */
trait RevisionDeclaration {}

/**
 * @property int $id
 * @property string $title
 * @property string $revision
 * @property string $note
 * @property string $password
 */
class StoredRecord extends ActiveRecord {}

/**
 * @property string|null $title
 * @property-read string $note
 * @property-write string $password
 */
final class DraftRecord extends StoredRecord implements SlugDeclaration
{
    use RevisionDeclaration;

    public function getNote(): string
    {
        return 'computed';
    }
}

function acceptsInt(int $value): void {}
function acceptsString(string $value): void {}

function inheritedAttributes(DraftRecord $record): void
{
    \Mago\confirm($record->getAttribute('id'), 'int');
    \Mago\confirm($record->getAttribute('title'), 'null|string');
    \Mago\confirm($record->getAttribute('revision'), 'int');
    \Mago\confirm($record->getAttribute('slug'), 'string');
    acceptsInt($record->getAttribute('id'));
    acceptsString($record->getAttribute('slug'));
    // @mago-expect analysis:invalid-argument
    acceptsString($record->getAttribute('revision'));

    // A nearest directional tag must hide the parent's writable stored-attribute declaration.
    \Mago\confirm($record->getAttribute('note'), 'mixed');
    \Mago\confirm($record->getAttribute('password'), 'mixed');
    \Mago\confirm($record->getAttribute('isNewRecord'), 'mixed');
    \Mago\confirm($record->getAttribute('missing'), 'mixed');
}
