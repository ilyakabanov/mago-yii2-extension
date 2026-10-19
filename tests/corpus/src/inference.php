<?php

declare(strict_types=1);

namespace Corpus;

use yii\di\Container;
use yii\web\Application;
use yii\web\HeaderCollection;
use yii\web\View;

function acceptsString(string $value): void {}
function acceptsInt(int $value): void {}
function acceptsView(View $value): void {}

function configuredServices(Container $container, Application $app): void
{
    acceptsView($container->get('view-service'));
    acceptsView($app->get('view'));
    acceptsView($app->view);
    acceptsString($app->params['adminEmail']);
    // @mago-expect analysis:invalid-argument
    acceptsInt($app->params['adminEmail']);
}

function headers(HeaderCollection $headers, bool $first): void
{
    acceptsString($headers->get('Content-Type', 'text/plain'));
    acceptsString($headers->get(default: 'text/plain', name: 'Content-Type'));
    // @mago-expect analysis:invalid-argument
    acceptsInt($headers->get('Content-Type', 'text/plain'));
}

function behavior(Article $article): void
{
    acceptsString($article->slug);
    acceptsString($article->publish('hello'));
    acceptsInt($article->getAttribute('id'));
    // @mago-expect analysis:invalid-argument
    acceptsInt($article->publish('hello'));
}
