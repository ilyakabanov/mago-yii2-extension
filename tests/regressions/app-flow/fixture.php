<?php

declare(strict_types=1);

namespace Regression\AppFlow;

use function Mago\confirm;

class BaseApplication {}

final class WebApplication extends BaseApplication {}

final class ConsoleApplication extends BaseApplication {}

final class Facade
{
    /** @var WebApplication */
    public static $app;
}

function initialApplication(): void
{
    confirm(Facade::$app, 'Regression\\AppFlow\\WebApplication');
}

function assignedConsole(ConsoleApplication $console): void
{
    Facade::$app = $console;
    confirm(Facade::$app, 'Regression\\AppFlow\\ConsoleApplication');
}

function assignedBase(BaseApplication $base): void
{
    Facade::$app = $base;
    confirm(Facade::$app, 'Regression\\AppFlow\\BaseApplication');
}

function assignedNullable(?WebApplication $web): void
{
    Facade::$app = $web;
    confirm(Facade::$app, 'Regression\\AppFlow\\WebApplication|null');
}

function assignedUnion(ConsoleApplication $console, WebApplication $web, bool $condition): void
{
    Facade::$app = $condition ? $console : $web;
    confirm(Facade::$app, 'Regression\\AppFlow\\ConsoleApplication|Regression\\AppFlow\\WebApplication');

    if (Facade::$app instanceof ConsoleApplication) {
        confirm(Facade::$app, 'Regression\\AppFlow\\ConsoleApplication');
    }
}
