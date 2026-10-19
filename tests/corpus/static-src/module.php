<?php

declare(strict_types=1);

namespace Corpus;

final class ConfiguredModule extends \yii\base\Module
{
    public function adminEmail(): string
    {
        return $this->params['adminEmail'];
    }
}
