# Testing

`composer test` runs PHPUnit and a real Mago worker corpus. `composer analyze` checks the package source; `composer format:check` checks its formatting. `composer validate --strict` validates package metadata.

The corpus checks startup, configured services, nullable lookups, application properties, params, behaviors, attributes, custom query classes, row shapes, relationships, batch iteration, headers and logging. It combines narrow typed consumers, negative `@mago-expect` checks and exact `Mago\confirm` assertions. The separate sparse-patch corpus checks both Yii facade spellings and verifies that original facade methods survive patching.

The unit suite exercises configuration normalization against the real Yii container and service locator. It also checks type construction and provider logic. Its offline SDK helper uses SDK internals only in tests; production code uses the public SDK. The native corpus is necessary because unit metadata cannot prove worker dispatch and protocol encoding.

The plugin enables function/method provider memoization, including callable signatures, only within one frozen analysis generation. Configuration and metadata inputs must remain fixed; future providers must not depend on invocation location, order or externally mutable state. This does not cache property providers or invalidate configuration result caches. Restart analysis after configuration changes.

Before the CI guard additions, the suite passed on both Yii 2.0.55 and Yii 22 revision `32a14a1` with Mago 1.58.2 and PHP 8.5.11: 148 PHPUnit tests with 1594 assertions, worker registration, both corpora, source analysis, formatting and the harness self-test. See [compatibility](compatibility.md) for the separate historical upgrade matrix. With the CI guard additions, local validation on Yii 2.0.55 / PHP 8.5.11 / Mago 1.58.2 passes 174 tests with 1624 assertions; the expanded suite has not yet been rerun locally on Yii 22 or PHP 8.3, nor executed on GitHub Actions.

Restored unit coverage includes all nine original portable params-shape datasets and the complete 300-key shape, DI aliases/definition forms, symlink and legacy application-type validation, malformed singleton definitions, and the runtime check that `getAttribute()` does not invoke virtual getters. This does not mean every original unit/cache test has been migrated. New worker cases check verified single-row query bindings and precise return contracts, including host substitution for declaring-owner generic overrides, inherited behavior lookup and native/pseudo owner precedence, property precedence, and compatible application assignments. Complex or multi-template `asArray()` inference falls back to native analysis; generic forwarded behavior methods remain unresolved.

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

All 643 comparisons execute on both Yii versions: 634/643 default and 636/643 with `--project-patches`, with identical full failure and native diagnostic signatures before and after provider memoization, including nine native params diagnostics. `--project-patches` enables the separately tested concrete-model workaround for the Web profile; other profiles retain their original configuration. Both modes still exit with failure. See [project-specific stubs](project-stubs.md) for its scope and unsuccessful alternatives.

CI keeps the incomplete full parity step visible (`continue-on-error`) and then requires a separate no-regression check:

```sh
composer test:parity -- --json > /tmp/yii2-parity.json # still exits 1 on known mismatches
php tests/check-parity-regressions.php /tmp/yii2-parity.json
```

The guard compares all six profiles against the fixed `tests/Parity/current-results.json` report, including counts and multisets of mismatch/native diagnostic signatures (code, message, primary relative filename and line). Only removal of known issues is accepted; new issues, changed failure messages, duplicates and incomplete or invalid reports fail. It prints actual passing counts and remaining failures: green means no regression, not complete parity. The snapshot is not automatically refreshed.

This remains a report, not a Mago inference baseline or suppression list: the guard compares outcomes, not type correctness, and changes neither actual nor expected types. `composer test:parity` still exits 1 until all 643 comparisons pass. Regenerating fixtures uses the read-only `--generate-patch` command described in [the harness README](../tests/Parity/README.md).

## Intentionally red diagnostics

[Regression reproduction commands](../tests/regressions/README.md) cover five native static-application flow mismatches, an unknown dynamic method call, inherited generic property/provider and forwarded behavior-method mismatches, and all six original append inputs. Three append inputs fail because Mago appends at `0` instead of the PHP 8.3+ negative successor; a literal-array control fails independently of Yii. The append config is under `tests/fixtures/config`, not the default corpus.

These fixtures deliberately exit nonzero and are outside `composer test`. They preserve expected semantics, not suppressions or passing substitutes. Native direct property access resolves the generic `float` control; the unresolved `T` is specifically a public SDK metadata/provider boundary.

## Composer consumer

From `tests/consumer`, run `composer install`, then `php vendor/bin/mago-yii2-stubs config/analysis.php .mago/Yii.php`. Run the repository's installed Mago binary from that directory: `../../vendor/bin/mago analyze`. The worker loads the consumer's own Composer autoloader. This checks path-package installation, the exported bin entrypoint, and actual application analysis together. The separate consumer analysis passes with Yii 2.0.55 and Mago SDK 1.58.2.

Use `MAGO_BINARY` to select another compatible Mago binary for corpus/parity runners. Composer's Mago launcher downloads its native binary on first use, so that first invocation needs network access.
