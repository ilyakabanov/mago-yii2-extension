# mago-yii2-extension

A Composer development dependency that teaches Mago about Yii2 application configuration, DI services, behaviors and ActiveRecord queries. It uses the PHP SDK and an external worker; PHPStan is not a runtime dependency.

The package targets PHP 8.3+, Mago 1.58.2+, Yii 2.0.55 and Yii 22. The migration is not complete: **634 of 643 original type assertions pass on Mago 1.58.2**, unchanged from 1.57.0. The remaining cases are recorded in [the compatibility report](docs/compatibility.md). The focused integration corpus and package unit tests pass.

## Install from this checkout

The package has not been published to Packagist. Add this checkout as a Composer path repository in the consuming application:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/absolute/path/to/mago-yii2-extension",
            "options": {
                "versions": {"yii2/mago-extension": "dev-main"}
            }
        }
    ],
    "require-dev": {"yii2/mago-extension": "dev-main"}
}
```

Run `composer update yii2/mago-extension --with-all-dependencies`. The application keeps its existing Yii asset dependency setup. The development checkout uses a Bower replacement only for analyzer tests.

## Configure the worker

Create an analysis configuration such as `config/mago.php`:

```php
<?php

return [
    'mago' => ['application_type' => yii\web\Application::class],
    'components' => [
        'view' => yii\web\View::class,
        'user' => [
            'class' => yii\web\User::class,
            'identityClass' => app\models\User::class,
        ],
    ],
    'container' => ['definitions' => ['mailer' => app\services\Mailer::class]],
    'behaviors' => [app\models\Post::class => [app\behaviors\SlugBehavior::class]],
    'params' => ['adminEmail' => 'admin@example.test'],
];
```

This file is executed in the analyzer worker. It returns analysis data; loading it does not construct a Yii application or register services in the real application. Factories are reflected, not invoked. It may reuse application configuration, but should not bootstrap HTTP handling, contact the database or print output. The legacy `phpstan.application_type` setting is also accepted; `mago.application_type` takes precedence.

Create `.mago/worker.php`:

```php
<?php

use Mago\Sdk\Worker;
use yii2\extensions\mago\Yii2Extension;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Worker(Yii2Extension::create(dirname(__DIR__) . '/config/mago.php')))->run();
```

Generate a sparse analysis patch for `Yii::$app`, `yii\BaseYii::$app` and inherited module params:

```sh
vendor/bin/mago-yii2-stubs config/mago.php .mago/yii.php
```

Add the worker and patch to the application's `mago.toml`:

```toml
[source]
paths = ["app"]
includes = ["vendor"]
patches = [".mago/yii.php"]

[extension-hosts.yii2]
command = ["php", ".mago/worker.php"]
```

Use the application's own source directories instead of `app`. For an advanced Yii template, run separate analyses with separate Web and console configs/patches. Regenerate the patch after changing application type or params. Keep generated patches out of runtime autoloading. Ordinary Yii facade methods remain available because a Mago patch refines existing declarations instead of replacing the class.

Run `vendor/bin/mago extension validate` and `vendor/bin/mago analyze`. Restart a long-running analysis command after changing the PHP analysis configuration; the worker loads it once. The default generic component mapping is `['user' => 'identityClass']`; pass a second argument to `Yii2Extension::create()` to replace that mapping.

## Inference covered

- Configured application properties, recursive params shapes and generic components.
- Container definitions, singletons, aliases, object instances and typed factories; unresolved definitions remain `object`.
- Component IDs through `ServiceLocator::get()`, with `throwException` nullability.
- Behavior properties and method signatures, inherited PHPDoc attributes and `getAttribute()`.
- ActiveRecord static factories, custom queries, `asArray()`, relations, fluent calls and batch iteration.
- Header return types and logger message tuples, including Yii 22 PSR message payloads.

Params keys containing control characters or `*/` cannot safely be inserted into PHPDoc. The generator omits the module params patch for that configuration; the SDK provider still preserves these exact keys for property accesses where Mago dispatches it.

## Develop

```sh
composer install
composer test
composer analyze
composer format:check
composer test:parity-harness
composer test:parity
```

`test:parity` deliberately exits with failure while original expectations remain unmatched. It does not suppress known differences. See [testing](docs/testing.md), [compatibility](docs/compatibility.md), and the complete [consumer application](tests/consumer).

The reference tests and configuration normalization derive from `yii2-extensions/phpstan` under the BSD 3-Clause license. See [NOTICE](NOTICE.md) and [LICENSE](LICENSE).
