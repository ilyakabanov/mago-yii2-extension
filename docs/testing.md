# Testing

`composer test` runs PHPUnit and a real Mago worker corpus. `composer analyze` checks the package source; `composer format:check` checks its formatting. `composer validate --strict` validates package metadata.

The corpus checks startup, configured services, nullable lookups, application properties, params, behaviors, attributes, custom query classes, row shapes, relationships, batch iteration, headers and logging. It combines narrow typed consumers, negative `@mago-expect` checks and exact `Mago\confirm` assertions. The separate sparse-patch corpus checks both Yii facade spellings and verifies that original facade methods survive patching.

The unit suite exercises configuration normalization against the real Yii container and service locator. It also checks type construction and provider logic. Its offline SDK helper uses SDK internals only in tests; production code uses the public SDK. The native corpus is necessary because unit metadata cannot prove worker dispatch and protocol encoding.

## Original test migration

`composer test:parity` executes all 31 original inference fixtures across Web, console, custom application, params, unusual params keys and definition-form profiles. There are 638 exact assertions and 5 supertype assertions. The test-only plugin uses Mago's native type comparator, with containment in both directions for exact types. It counts every assertion and fails on missing types or worker errors.

The harness self-test must observe three passing and two intentionally failing comparisons. It distinguishes an exact `int` type from the literal `1`. It also preserves late-static types inside intersections and generic parameters. Yii-version-dependent logger payload expectations are expanded from the installed framework's metadata.

The original PHPStan inference tests assert types independently of other diagnostics. This runner likewise reports native diagnostics separately; add `--strict-native` to make those diagnostics fail the run. Known inference differences always fail the default parity command. The focused production corpus rejects unexpected warnings and errors.

Useful commands:

```sh
php tests/run-parity.php --profile web
php tests/run-parity.php --profile params --json
php tests/run-parity.php --self-test
php tests/run-parity.php --strict-native
php tests/run-parity.php --project-patches
```

`--project-patches` enables the separately tested concrete-model workaround for the Web profile; other profiles retain their original configuration. It currently yields 636/643 and still exits with failure. See [project-specific stubs](project-stubs.md) for its scope and unsuccessful alternatives.

`tests/Parity/current-results.json` is a report, not a suppression baseline. Regenerating fixtures uses the read-only `--generate-patch` command described in [the harness README](../tests/Parity/README.md).

## Composer consumer

From `tests/consumer`, run `composer install`, then `php vendor/bin/mago-yii2-stubs config/analysis.php .mago/Yii.php`. Run the repository's installed Mago binary from that directory: `../../vendor/bin/mago analyze`. The worker loads the consumer's own Composer autoloader. This checks path-package installation, the exported bin entrypoint, and actual application analysis together.

Use `MAGO_BINARY` to select another compatible Mago binary for corpus/parity runners. Composer's Mago launcher downloads its native binary on first use, so that first invocation needs network access.
