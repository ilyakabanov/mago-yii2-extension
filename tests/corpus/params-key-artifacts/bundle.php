<?php

declare(strict_types=1);

namespace ParamsKeyArtifacts;

use function Mago\confirm;

final class BundleModule extends \yii\base\Module
{
    public function check(): void
    {
        confirm($this->params["both'\"quotes"], 'float');
        confirm($this->params['ending\\']['both\'\"'], 'array<array-key, mixed>');
        confirm($this->params['ending\\']['list'][1], 'string');
        confirm($this->params['09'], 'bool');
        confirm($this->params['-09'], 'int');
        confirm($this->params[-1], 'null');
        confirm($this->params[123], 'float');
        confirm($this->params["new\nline"], 'int');
        confirm($this->params['new\\nline'], 'bool');
        confirm($this->params["null\0key"], 'bool');
        confirm($this->params["*/'; throw new \\RuntimeException('injected'); //"], 'bool');
    }
}
