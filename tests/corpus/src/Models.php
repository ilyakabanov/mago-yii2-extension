<?php

declare(strict_types=1);

namespace Corpus;

/**
 * @property int $id
 * @property string $title
 */
class Article extends \yii\db\ActiveRecord
{
}

/** @property string $slug */
class SlugBehavior extends \yii\base\Behavior
{
    public function publish(string $title): string
    {
        return $title;
    }
}
