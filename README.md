# mago-yii2-extension

A Composer development dependency that teaches Mago about Yii2 application configuration, DI services, behaviors and ActiveRecord queries. It uses the PHP SDK and an external worker; PHPStan is not a runtime dependency.

The package targets PHP 8.3+, Mago 1.58.2+, Yii 2.0.55 and Yii 22. The migration is not complete: **634 of 643 original type assertions pass on Mago 1.58.2**, unchanged from 1.57.0. The remaining cases are recorded in [the compatibility report](docs/compatibility.md). On both Yii versions with PHP 8.5.11, **238 PHPUnit tests / 1911 assertions** and all four required corpus checks pass, including 107 params-key helper confirmations.

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

For exact params keys that cannot safely appear in PHPDoc, opt in to a companion key helper with an optional third argument (the two-argument command still works):

```sh
vendor/bin/mago-yii2-stubs config/mago.php .mago/yii.php .mago/ParamsKeys.php
```

Use these source entries, keeping your other includes and patches:

```toml
[source]
paths = ["app"]
includes = ["vendor", ".mago/ParamsKeys.php"]
patches = [".mago/yii.php"]
```

The helper supplies ordinary analysis metadata under `source.includes`; only the sparse Yii patch belongs in `source.patches`. Never runtime-require or autoload either artifact. The PHP API is `StubGenerator::generateWithParamsKeys($services)`, returning `array{patch: string, keys: string}`. Generation does not start a Yii application or execute configured factories. Regenerate both artifacts together after application/params changes and restart analysis. Existing consumers must explicitly opt in; they are not migrated automatically. See [params-key coverage and configuration](tests/regressions/params-keys/README.md).

Run `vendor/bin/mago extension validate` and `vendor/bin/mago analyze`. Restart a long-running analysis command after changing the PHP analysis configuration; the worker loads it once. The default generic component mapping is `['user' => 'identityClass']`; pass a second argument to `Yii2Extension::create()` to replace that mapping.

## Inference covered

- Configured application properties, recursive params shapes and generic components.
- Container definitions, singletons, aliases, object instances and typed factories; unresolved definitions remain `object`.
- Component IDs through `ServiceLocator::get()`, with `throwException` nullability.
- Behavior properties and method signatures, including bound-checked method templates inferred from whole parameter types; inherited PHPDoc attributes and `getAttribute()`.
- ActiveRecord static factories, custom queries, `asArray()`, relations, fluent calls and batch iteration; multi-template custom queries when one row position is symbolically proven, preserving unrelated arguments.
- Header return types and logger message tuples, including Yii 22 PSR message payloads.

Single-file generation preserves ordinary quote/backslash keys, but omits the entire Module params declaration if any nested key contains control bytes, `*/`, both quote delimiters or a trailing backslash. The SDK provider preserves exact keys where Mago dispatches it; the optional companion helper above also supports inherited implicit reads with all actual keys, including NUL/control bytes. Query and behavior inference remains conservative for unsupported template patterns; see [compatibility](docs/compatibility.md).

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
