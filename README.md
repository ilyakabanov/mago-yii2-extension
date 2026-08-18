# Mago Yii2 Extension

> Experimental: this project is in early development and does not provide Yii2-specific analysis features yet.

A Mago extension intended to add Yii2 framework awareness to the [Mago](https://github.com/carthage-software/mago) PHP static analyzer.

## Status

The repository currently contains the extension and analyzer-plugin scaffolding. Yii2-specific providers, hooks, and rules will be added incrementally.

## Identity

- Composer package: `ilyakabanov/mago-yii2-extension`
- PHP namespace: `Ilyakabanov\MagoYii2`
- Mago extension identifier: `yii2/mago-extension`
- Analyzer plugin identifier: `yii2/framework`

## Using the extension during development

Install dependencies:

```shell
composer install
```

Create a worker entrypoint in the application, for example `.mago/extensions.php`:

```php
<?php

declare(strict_types=1);

use Ilyakabanov\MagoYii2\Yii2Extension;
use Mago\Sdk\Worker;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Worker(Yii2Extension::create()))->run();
```

Register the worker in `mago.toml`:

```toml
[extension-hosts.yii2]
command = ["php", ".mago/extensions.php"]
```

Enable the analyzer plugin when needed:

```toml
[analyzer]
plugins = ["yii2/framework"]
```

## Development

Run all project checks with:

```shell
just check
```

Useful focused commands:

```shell
just format
just test
just lint
just analyze
just test-corpus
```

The corpus test starts the real extension worker and verifies that the extension can be registered by Mago. Yii2-specific corpus cases will be added together with their corresponding features.

## License

MIT.
