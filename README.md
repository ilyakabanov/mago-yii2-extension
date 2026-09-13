# Mago Yii2 Extension

> Experimental: this project is in early development and does not provide Yii2-specific analysis features yet.

A Mago extension intended to add Yii2 framework awareness to the [Mago](https://github.com/carthage-software/mago) PHP static analyzer.

## Status

The package currently provides:

- a shared `yii2.mago.toml` preset with Yii2-oriented formatter settings;
- 14 explicitly configured Mago core lint rules;
- the `yii2/private-property-underscore` linter rule;
- an analyzer-plugin scaffold, without Yii2-specific type inference yet.

The configured core rules are `array-style`, `block-statement`, `class-name`, `constant-name`, `lowercase-keyword`, `lowercase-type-hint`, `method-name`, `no-closing-tag`, `no-short-opening-tag`, `no-side-effects-with-declarations`, `no-trailing-space`, `optional-param-order`, `require-namespace`, and `single-class-per-file`.

This is not a replacement for PHPStan or PHPCS yet. The preset provides partial Yii2 Coding Standards coverage. Built-in rules not listed above retain their normal Mago defaults.

**Known fix limitation:** the private-property rule currently renames declarations without updating their usages. Do not apply its automatic fixes; use lint diagnostics only.

## Identity

- Composer package: `ilyakabanov/mago-yii2-extension`
- PHP namespace: `Ilyakabanov\MagoYii2`
- Mago extension identifier: `yii2/mago-extension`
- Analyzer plugin identifier: `yii2/framework`

## Installation

Requires PHP 8.1+, Composer 2.2+, and Mago 1.47.1+. Composer installs Mago together with this package.

Until a release is published, install from a local checkout. Run these commands **in the consuming Yii2 application**, replacing the path:

```shell
composer config repositories.mago-yii2 path /path/to/mago-yii2-extension
composer require --dev 'ilyakabanov/mago-yii2-extension:@dev'
```

## Connect to a Yii2 project

Add this line at the top of the application's `mago.toml`:

```toml
extends = "vendor/ilyakabanov/mago-yii2-extension/yii2.mago.toml"
```

The preset connects the packaged worker automatically; no custom PHP entrypoint or Yii2 bootstrap is needed. Remove any previous manual registration to avoid registering the extension twice.

The shared preset does not set the application's PHP version or source paths. Define them in the application's `mago.toml`, for example:

```toml
[source]
paths = ["controllers", "models", "components", "views"]
includes = ["vendor"]
```

Validate the connection, then run Mago checks:

```shell
vendor/bin/mago extension validate
vendor/bin/mago lint
vendor/bin/mago format --check
vendor/bin/mago analyze
```

The analyzer currently runs Mago's standard analysis; Yii2-specific inference is not implemented yet.

### Change settings in your project

Add project-specific overrides after `extends` instead of editing files in `vendor`:

```toml
[formatter]
single-quote = false

[linter.rules]
array-style = { level = "warning" }
```

Built-in rules support `enabled = false`, but options for custom `yii2/*` rules are not exposed through `[linter.rules]`. To disable the Yii2 extension while retaining formatter and built-in rule settings:

```toml
[extension-hosts.yii2]
enabled = false
```

Mago replaces scalar settings but **appends arrays**, including exclusions and worker commands. To replace an inherited command, disable its host and register a new host under a different name.

The preset assumes `mago.toml` is in the application root and Composer uses the default `vendor` and `vendor/bin` directories. For custom layouts, adjust `extends` and register a separate host pointing to the installed worker.

### Configuration files

- **`yii2.mago.toml`** is the shared preset for consuming applications.
- **`mago.toml`** checks the extension's own source and tests. Do not import it into your application.

## Development

Install dependencies and run all project checks:

```shell
composer install
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

The example application in [`tests/consumer/`](tests/consumer/) contains a Composer manifest, a clean PHP example, and three Mago configurations:

- `mago.toml` imports the shared preset;
- `mago-overrides.toml` changes quote formatting and the built-in array rule's severity;
- `mago-disabled.toml` disables that rule and the extension host.

Intentional violations and expected formatter output live next to the integration test in [`tests/Integration/Fixtures/`](tests/Integration/Fixtures/).

The PHPUnit integration tests install copies of the clean consumer with Composer and add violations only inside temporary projects. They verify symlinked and mirrored installations, worker registration, the configured core rules and Yii2 path exclusions, the custom lint rule, project overrides, archive contents, and that the clean example passes lint and format checks. Tests reuse the installed dependency versions recorded in `composer.lock` without downloads and leave source examples and fixtures unchanged.

The corpus test starts the real extension worker and verifies that the extension can be registered by Mago. Yii2-specific corpus cases will be added together with their corresponding features.

## License

MIT.
