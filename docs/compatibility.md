# Compatibility report

Verified locally on 2026-10-20 with PHP 8.5.11, Mago 1.58.2 and PHPUnit 12.5.40. The pre-CI-guard 148-test / 1594-assertion suite, worker registration, both corpora, harness self-test, source analysis and formatting were verified on both Yii 2.0.55 and Yii 22 development revision `32a14a1`. The final installed Yii version is 2.0.55. Only PHP 8.5 was run locally; the CI matrix additionally defines PHP 8.3 checks.

The package is installed, its Composer metadata validates, and a separate consumer installs it through a Composer path repository. The consumer's worker successfully analyzes application code through the consumer's own autoloader with Yii 2.0.55 and Mago SDK 1.58.2. Nothing has been published to Packagist or pushed to a remote repository.

## Verification

| Check | Result |
| --- | --- |
| Package PHPUnit suite before CI guard additions | 148 tests, 1594 assertions pass on both Yii versions |
| Real Mago worker registration | Pass |
| Focused native inference corpus | Pass |
| Sparse BaseYii/Module patch corpus | Pass; original facade methods preserved |
| Analysis of package source and formatting | Pass |
| Original assertion harness self-test | Pass; deliberate failures are detected |
| Original exact/supertype assertions | 634 of 643 pass |
| Separate Composer consumer | Installation and analysis pass |

## Original test profiles

| Profile | Passing assertions | Original assertions |
| --- | ---: | ---: |
| Web, queries, behaviors, services, headers, logging | 526 | 530 |
| Console application | 29 | 29 |
| Custom application | 5 | 5 |
| Params and control flow | 34 | 39 |
| Unusual params keys | 17 | 17 |
| DI definition forms | 23 | 23 |
| Total | 634 | 643 |

There are 638 exact-type and 5 supertype assertions. All 643 comparisons were verified on both Yii versions: 634/643 default and 636/643 with project patches, with identical full failure and native diagnostic signatures before and after provider memoization. Original fixtures, expected types and assertion counts are retained. The complete current report is [current-results.json](../tests/Parity/current-results.json). The parity command exits with failure until all remaining assertions match; no baseline suppresses those mismatches. CI additionally requires a no-regression comparison against that fixed report, accepting only removal of known mismatch/native diagnostic signatures. A green guard still reports the real 634/643 result; it is not a Mago inference baseline, does not alter type expectations and never automatically refreshes the snapshot. See [testing](testing.md) for commands and validation rules.

## Historical Mago 1.58.2 upgrade checkpoint

Updated from 1.57.0 to [1.58.2](https://github.com/carthage-software/mago/releases/tag/1.58.2), the latest stable Composer release available on 2026-10-20. Only the Mago package was upgraded; the final installed Yii version remains 2.0.55. The package now requires `^1.58.2`.

[Mago 1.58.0](https://github.com/carthage-software/mago/releases/tag/1.58.0) changes the analyzer protocol from MANA 2.0 to 2.1. The Composer package updates the native binary and PHP SDK together. The existing Yii plugin needs no source changes; the independent Composer consumer was also updated and its real worker analysis passed.

This table records the earlier upgrade checkpoint, before the coverage additions below.

| Check | Mago 1.57.0 | Mago 1.58.2 |
| --- | --- | --- |
| Original default assertions | 634/643 | 634/643 |
| Original assertions with project patches | 636/643 | 636/643 |
| Params native errors | 9 | 9 |
| PHPUnit | 111 tests, 384 assertions | 111 tests, 384 assertions |
| Worker corpus and harness self-test | Pass | Pass |

The default and project-patch runs preserve every comparison, mismatch location and diagnostic message on Yii 2.0.55. Both runs, PHPUnit, the corpus, the harness self-test and package source analysis were also repeated on Yii 22 revision `32a14a1`, with the same pass/fail totals. Composer validation and formatting checks pass on the final Yii 2.0.55 installation.

None of the nine assertion differences below is resolved by this upgrade. SDK `ClassLikeMetadata`, `PropertyMetadata` and `Codebase` are byte-identical to 1.57.0: generic parent bindings remain unavailable through those APIs. The new SDK providers describe thrown exception types, not generic inheritance or configuration-cache dependencies. The upgrade alone did not restore the unit-test cases identified in the migration audit; subsequent coverage additions are described below. Reference fixtures, expected types and failure handling were not changed.

## Subsequent fixes and restored coverage

At this earlier fixes checkpoint, the expanded PHPUnit suite passed **145 tests / 1584 assertions** on both Yii 2.0.55 and Yii 22 revision `32a14a1`; worker registration, both corpora, source analysis, formatting and the harness self-test passed. All 643 original comparisons still run: **634/643** default and **636/643** with project patches, with the same mismatch locations and nine native params diagnostics as before these fixes.

- Custom query row rewriting now requires a single template whose row binding is verified by Mago's symbolic subtype comparator, rather than assuming the first generic argument is the row. Unused/key-only parameters are not mistaken for row bindings; complex or multi-template `asArray()` inference falls back to native analysis. Precise bound `one()`/`all()` results and custom `findOne()`/`findAll()` return contracts are preserved, including supported `static`/`$this` contracts. Custom generic overrides, including inherited ones, are checked against the true declaring owner and template identity, then left to host substitution so non-null `one()` and `list<T>` `all()` contracts retain their precision.
- Behavior methods use inherited declaring-method lookup. Native owner methods, including inherited ones, retain precedence and native signatures; behavior methods take precedence over owner PHPDoc pseudo methods, which are distinguished from native declarations. Direct behavior PHPDoc methods already worked. Forwarded generic behavior-method substitution remains unresolved; this fix does not claim general template instantiation.
- Restored unit coverage includes the complete 300-key params shape and nine original portable shape datasets, DI aliases/definition forms, symlink and legacy application-type validation, malformed singleton definitions, and the runtime check that `getAttribute()` does not invoke virtual getters. Property precedence and directional PHPDoc contracts also have focused coverage. This is not a claim that all original unit tests or result-cache behavior have been migrated.

The separate [red regression reproducers](../tests/regressions/README.md) isolate native application flow, unknown dynamic calls, the property metadata boundary and unresolved forwarded behavior-method generics. They also expose a native negative-index append bug in three of six original append cases: Mago uses key `0` instead of `-4` or `-1`. A minimal literal-array control reproduces it without Yii params inference. These diagnostics remain outside the green default suite and are not suppressed or counted as passing tests.

## Remaining differences

| Cases | Trigger | Current Mago behavior |
| ---: | --- | --- |
| 1 | `Invoice::$method()` with an unknown method name | Neither the targeted snapshot nor the lazy expression-type query supplies an inferred type to the test hook. The comparison remains a failure, rather than being assumed to be `mixed`. |
| 2 | A model inherits `@property T $value` through `@extends TypedValueRecord<float>` | Native direct property access resolves `float`, but public SDK metadata exposes unresolved declaring-owner `T`; the receiver has no generic parameters and class metadata exposes no parent binding. This affects `getAttribute('value')` and the `asArray()` row shape, not all native generic inference. |
| 1 | Object-static `$invoice::find()->one()` | Adapted `ActiveRecordQueryDynamicStaticMethodReturnType.php:178` supplies late-static `Invoice` where the fixture expects ordinary `Invoice`. The public invocation cannot safely distinguish this from genuine `$this::` contracts. The class-string assertion at line 176 passes. |
| 5 | Assigning another application type to `Yii::$app`, then reading or narrowing it | Mago retains the declared application type supplied by the sparse patch instead of tracking the assigned console/base/nullable/union type as PHPStan does. |

An optional project-specific property patch improves the result to **636/643**, without changing reference files or expectations. It is enabled with `php tests/run-parity.php --project-patches`; the default run remains 634/643. The [stub experiment report](project-stubs.md) documents the working annotation, its source/include boundary, and query/application declarations that introduced regressions. Broader application declarations permit compatible assignment tracking but lose the configured Web type elsewhere.

The params profile additionally reports nine native diagnostics for intentionally incompatible assignments and a redeclared property. The original PHPStan inference tests compare inferred types independently of such diagnostics. The runner reports them separately, and `--strict-native` makes them fail the run as well. The focused application corpus permits no unexpected warnings or errors.

The original PHPStan result-cache dependency extension has no direct SDK equivalent here. Configuration is immutable for one worker lifetime. Restart a running analysis after changing configuration and regenerate the sparse patch after application/params changes. No PHPStan cache dependency API is emulated. Function/method provider memoization, including callable signatures, is limited to deterministic invocations within one frozen analysis generation; it neither caches property providers nor invalidates configuration result caches.

The patch generator omits the Module params declaration if a key contains a control character or `*/`, which cannot be inserted safely into its PHPDoc representation. The semantic provider retains exact unusual keys. Implicit `$this->params` accesses that bypass Mago's provider dispatch require a representable patch or an application declaration.

## Investigation evidence

The source snapshot is `yii2-extensions/phpstan` revision `94504ad1b50bdfb4e5c470e898b6719140faee70`. Its 209 copied reference files were compared byte for byte with the local source checkout. Structural exploration used the repository graph, with direct source checks for its reported parser gaps. Runtime conclusions above come from actual PHPUnit, Composer and Mago commands.

The requested REA CLI was downloaded and its readiness check was run. REA 6.7.0 reported Node 25.2.0 as unsupported and the installed skill/registration as stale; its MCP tools were unavailable in the session. No REA artifact-analysis result is claimed. The supplied target is a complete PHP source repository, for which the installed REA skill directs ordinary source analysis. Global REA configuration was not changed.

The implementation follows the official [analyzer plugin API](https://mago.carthage.software/1.57.0/en/extensions/analyzer/overview/), [property provider contract](https://mago.carthage.software/1.57.0/en/extensions/analyzer/properties-and-initialization/), [vendor patch semantics](https://mago.carthage.software/1.57.0/en/guide/configuration/#patching-vendor-types), and [native corpus testing guidance](https://mago.carthage.software/1.57.0/en/extensions/development/testing-and-debugging/).
