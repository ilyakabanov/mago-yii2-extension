<?php

declare(strict_types=1);

namespace ParamsKeyArtifacts;

use function Mago\confirm;

final class Module extends \yii\base\Module
{
    public function check(): void
    {
        confirm($this->params['ordinary'], 'string');
        confirm($this->params['it\'s'], 'float');
        confirm($this->params['quote"d'], 'int');
        confirm($this->params['back\\slash'], 'string');
        confirm($this->params['new\\nline'], 'bool');
        confirm($this->params['nested']['it\'s'], 'bool');
        confirm($this->params['nested']['back\\slash'], 'int');
    }
}

final class Application extends \yii\web\Application
{
    public function check(): void
    {
        confirm($this->params['ordinary'], 'string');
        confirm($this->params['it\'s'], 'float');
        confirm($this->params['quote"d'], 'int');
        confirm($this->params['back\\slash'], 'string');
        confirm($this->params['new\\nline'], 'bool');
        confirm($this->params['nested']['it\'s'], 'bool');
        confirm($this->params['nested']['back\\slash'], 'int');
    }
}

final class Controller extends \yii\web\Controller
{
    public function check(): void
    {
        confirm($this->module->params['ordinary'], 'string');
        confirm($this->module->params['it\'s'], 'float');
        confirm($this->module->params['quote"d'], 'int');
        confirm($this->module->params['back\\slash'], 'string');
        confirm($this->module->params['new\\nline'], 'bool');
        confirm($this->module->params['nested']['it\'s'], 'bool');
        confirm($this->module->params['nested']['back\\slash'], 'int');
    }
}

function explicitReceiver(\yii\base\Module $module): void
{
    confirm($module->params['ordinary'], 'string');
    confirm($module->params['it\'s'], 'float');
    confirm($module->params['quote"d'], 'int');
    confirm($module->params['back\\slash'], 'string');
    confirm($module->params['new\\nline'], 'bool');
    confirm($module->params['nested']['it\'s'], 'bool');
    confirm($module->params['nested']['back\\slash'], 'int');
}
