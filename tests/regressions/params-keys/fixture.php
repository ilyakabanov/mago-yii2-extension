<?php

declare(strict_types=1);

namespace ParamsKeyRegression;

use function Mago\confirm;

final class Module extends \yii\base\Module
{
    public function checkKeys(): void
    {
        confirm($this->params['ordinary'], 'string');
        confirm($this->params['x*/y'], 'int');
        confirm($this->params["new\nline"], 'int');
        confirm($this->params['new\nline'], 'bool');
        confirm($this->params["null\0key"], 'bool');
        confirm($this->params["it's"], 'float');
        confirm($this->params['quote"d'], 'null');
        confirm($this->params['back\\slash'], 'string');
        confirm($this->params["tab\tkey"], 'null');
        confirm($this->params["control\x01\x07\x08\x1f\x7f"], 'int');
        confirm($this->params['é'], 'string');
        confirm($this->params[''], 'string');
        confirm($this->params[-5], 'bool');
        confirm($this->params[123], 'float');
        confirm($this->params['nested']['*/']["line\nbreak"]['ok'], 'bool');
        confirm($this->params['nested']['ordinary'], 'int');
        confirm($this->params['list'][0], 'string');
    }
}

final class Application extends \yii\web\Application
{
    public function checkKeys(): void
    {
        confirm($this->params['ordinary'], 'string');
        confirm($this->params['x*/y'], 'int');
        confirm($this->params["new\nline"], 'int');
        confirm($this->params['new\nline'], 'bool');
        confirm($this->params["null\0key"], 'bool');
        confirm($this->params["it's"], 'float');
        confirm($this->params['quote"d'], 'null');
        confirm($this->params['back\\slash'], 'string');
        confirm($this->params["tab\tkey"], 'null');
        confirm($this->params["control\x01\x07\x08\x1f\x7f"], 'int');
        confirm($this->params['é'], 'string');
        confirm($this->params[''], 'string');
        confirm($this->params[-5], 'bool');
        confirm($this->params[123], 'float');
        confirm($this->params['nested']['*/']["line\nbreak"]['ok'], 'bool');
        confirm($this->params['nested']['ordinary'], 'int');
        confirm($this->params['list'][0], 'string');
    }
}

final class Controller extends \yii\web\Controller
{
    public function checkKeys(): void
    {
        confirm($this->module->params['ordinary'], 'string');
        confirm($this->module->params['x*/y'], 'int');
        confirm($this->module->params["new\nline"], 'int');
        confirm($this->module->params['new\nline'], 'bool');
        confirm($this->module->params["null\0key"], 'bool');
        confirm($this->module->params["it's"], 'float');
        confirm($this->module->params['quote"d'], 'null');
        confirm($this->module->params['back\\slash'], 'string');
        confirm($this->module->params["tab\tkey"], 'null');
        confirm($this->module->params["control\x01\x07\x08\x1f\x7f"], 'int');
        confirm($this->module->params['é'], 'string');
        confirm($this->module->params[''], 'string');
        confirm($this->module->params[-5], 'bool');
        confirm($this->module->params[123], 'float');
        confirm($this->module->params['nested']['*/']["line\nbreak"]['ok'], 'bool');
        confirm($this->module->params['nested']['ordinary'], 'int');
        confirm($this->module->params['list'][0], 'string');
    }
}

function explicitReceiver(\yii\base\Module $module): void
{
    confirm($module->params['ordinary'], 'string');
    confirm($module->params['x*/y'], 'int');
    confirm($module->params["new\nline"], 'int');
    confirm($module->params['new\nline'], 'bool');
    confirm($module->params["null\0key"], 'bool');
    confirm($module->params["it's"], 'float');
    confirm($module->params['quote"d'], 'null');
    confirm($module->params['back\\slash'], 'string');
    confirm($module->params["tab\tkey"], 'null');
    confirm($module->params["control\x01\x07\x08\x1f\x7f"], 'int');
    confirm($module->params['é'], 'string');
    confirm($module->params[''], 'string');
    confirm($module->params[-5], 'bool');
    confirm($module->params[123], 'float');
    confirm($module->params['nested']['*/']["line\nbreak"]['ok'], 'bool');
    confirm($module->params['nested']['ordinary'], 'int');
    confirm($module->params['list'][0], 'string');
}
