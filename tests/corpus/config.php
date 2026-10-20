<?php

declare(strict_types=1);

use Corpus\Article;
use Corpus\SlugBehavior;
use yii\web\View;

return [
    'mago' => ['application_type' => yii\web\Application::class],
    'components' => ['view' => View::class, 'factory' => static fn () => new View()],
    'container' => ['definitions' => ['view-service' => View::class]],
    'behaviors' => [
        Article::class => [SlugBehavior::class],
        'Corpus\\BehaviorRegression\\ForwardingOwner' => ['Corpus\\BehaviorRegression\\InheritedBehavior'],
        'Corpus\\BehaviorRegression\\NativeOwner' => ['Corpus\\BehaviorRegression\\ConflictingBehavior'],
    ],
    'params' => ['adminEmail' => 'admin@example.test', 'nested' => ['enabled' => true]],
];
