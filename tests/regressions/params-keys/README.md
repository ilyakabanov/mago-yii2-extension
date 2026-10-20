# Params keys: default red probe and opt-in analysis bundle

## Reproduce and verify

Run from the repository root with the installed dependencies and native Mago binary:

```sh
php tests/regressions/params-keys/run.php
php tests/regressions/params-keys/run.php --with-key-helper
```

The **default command remains intentionally red** on Mago 1.58.2: exit 1, 68 exact
`Mago\confirm` checks, 34 passing explicit Module/Controller reads and 34 failing
inherited implicit Module/Application reads. An unsafe nested key makes the
single-file generator omit the entire Module params patch. This is not a full
migration/parity fix.

The **opt-in command passes all original 68 checks unchanged**, using a separate
key helper under `source.includes`. It additionally requires 28 ordinary-key
checks through the original single-file API and 11 bundle edge-case checks.
Only the expected successful Help-level confirmations are accepted: any other
native diagnostic, missing comparison or nonzero exit fails. This command is
required by `composer test:corpus`; the default red probe is not.

Set `MAGO_BINARY` to select another compatible binary, or pass
`--with-key-helper --mago-binary /absolute/path/to/mago`. The corpus runner forwards
its selected binary explicitly. The helper mode uses PHP target 8.3, native JSON
reports, report level `help`, fail level `warning`, `--skip-ignores` and
`--ignore-baseline`. Reports, stderr, generated artifacts and per-worker runtime
nonexecution logs are retained in a fresh temporary workspace, whose path is
printed. No analyzed fixture, helper or patch is required at runtime, and no Yii
application is started.

Coverage includes actual `*/`, LF and NUL bytes, distinct literal backslash+n,
both quote characters, trailing backslashes, tabs/control bytes, UTF-8, empty,
negative/integer and numeric-string keys, nested/list shapes and empty arrays.
Values keep their original widened primitive types; empty arrays remain
`array<array-key, mixed>`.

## Opt-in consumer configuration

The existing two-argument CLI and `StubGenerator::generate(ServiceMap): string`
remain available. To generate a companion helper, add a third output argument:

```sh
php vendor/bin/mago-yii2-stubs config/analysis.php .mago/Yii.php .mago/ParamsKeys.php
```

Register **both** artifacts in the consumer's Mago configuration:

```toml
[source]
paths = ["src"]
includes = ["vendor", ".mago/ParamsKeys.php"]
patches = [".mago/Yii.php"]
```

Keep any existing includes/patches entries. The sparse Yii patch belongs only in
`source.patches`; the helper is an ordinary analysis source under `source.includes`,
not a patch. **Never require either file or add either to Composer runtime
autoloading.** The CLI rejects colliding output destinations, including relative
aliases, symlinks and hardlinks, and validates both artifacts/destinations before
writing either. This is not an atomic two-file filesystem transaction.

The equivalent PHP API is:

```php
$bundle = StubGenerator::generateWithParamsKeys($services);
// $bundle['patch']: source.patches content
// $bundle['keys']: source.includes content
```

The helper's deterministic namespaced identity depends only on string key bytes,
not configured values. It declares safe constant names with ordinary PHP
`var_export` literals; only safe class-constant references enter the patch's
PHPDoc. Numeric-string coercion follows the actual configuration array keys.
Objects, resources and closures are neither serialized nor executed.

Regenerate both files together after params/application configuration changes and
restart running analysis: configuration is frozen for one worker generation.
Consumers must opt in and update their configuration themselves; existing
single-file outputs and original parity fixtures are not silently migrated.

## Single-file serialization boundary

Mago 1.58.2 retains PHPDoc escape bytes literally. The original generator wrongly
added PHP-style escapes to ordinary apostrophe/backslash keys (18/28 checks passed
in the native counterexample). It now chooses an absent quote delimiter and
preserves literal backslash bytes, yielding **28/28** through the original API.

Single-file generation still omits Module params when any nested key contains a
comment terminator, control byte, both quote delimiters or a trailing backslash.
It must not insert unsafe bytes or pretend that `\n` represents an actual LF in
PHPDoc. The semantic provider retains exact keys for supported dispatch routes;
the opt-in helper solves inherited implicit reads without unsafe PHPDoc or
runtime declarations. New helper classes/constants cannot be introduced inside
`sparse source.patches` (native `patch-introduces-new-symbol` /
`patch-introduces-new-constant`); they must remain in `source.includes`.
