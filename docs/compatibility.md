# Compatibility report

Verified locally on 2026-10-19 with PHP 8.5.11, Mago 1.57.0 and PHPUnit 12.5.40. Both Yii 2.0.55 and Yii 22 development revision `32a14a1` produced the same assertion results. Only PHP 8.5 was run locally; the CI matrix additionally defines PHP 8.3 checks.

The package is installed, its Composer metadata validates, and a separate consumer installs it through a Composer path repository. The consumer's worker successfully analyzes application code through the consumer's own autoloader. Nothing has been published to Packagist or pushed to a remote repository.

## Verification

| Check | Result |
| --- | --- |
| Package PHPUnit suite | 111 tests, 384 assertions pass |
| Real Mago worker registration | Pass |
| Focused native inference corpus | Pass |
| Sparse BaseYii/Module patch corpus | Pass; original facade methods preserved |
| Analysis of package source | Pass |
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

There are 638 exact-type and 5 supertype assertions. Original fixtures, expected types and assertion counts are retained. The complete current report is [current-results.json](../tests/Parity/current-results.json). The parity command exits with failure until all remaining assertions match; no baseline suppresses those mismatches.

## Remaining differences

| Cases | Trigger | Current Mago behavior |
| ---: | --- | --- |
| 1 | `Invoice::$method()` with an unknown method name | Neither the targeted snapshot nor the lazy expression-type query supplies an inferred type to the test hook. The comparison remains a failure, rather than being assumed to be `mixed`. |
| 2 | A model inherits `@property T $value` through `@extends TypedValueRecord<float>` | SDK property metadata returns unresolved `T`. This affects `getAttribute('value')` and the `asArray()` row shape. The class metadata API does not expose generic parent bindings. |
| 1 | `class-string<Invoice>::find()->one()` | Mago supplies a late-static `Invoice` receiver where the PHPStan fixture expects ordinary `Invoice`. The provider preserves the supplied late-static type. |
| 5 | Assigning another application type to `Yii::$app`, then reading or narrowing it | Mago retains the declared application type supplied by the sparse patch instead of tracking the assigned console/base/nullable/union type as PHPStan does. |

An optional project-specific property patch improves the result to **636/643**, without changing reference files or expectations. It is enabled with `php tests/run-parity.php --project-patches`; the default run remains 634/643. The [stub experiment report](project-stubs.md) documents the working annotation, its source/include boundary, and query/application declarations that introduced regressions. Broader application declarations permit compatible assignment tracking but lose the configured Web type elsewhere.

The params profile additionally reports nine native diagnostics for intentionally incompatible assignments and a redeclared property. The original PHPStan inference tests compare inferred types independently of such diagnostics. The runner reports them separately, and `--strict-native` makes them fail the run as well. The focused application corpus permits no unexpected warnings or errors.

The original PHPStan result-cache dependency extension has no direct SDK equivalent here. Configuration is immutable for one worker lifetime. Restart a running analysis after changing configuration and regenerate the sparse patch after application/params changes. No PHPStan cache dependency API is emulated.

The patch generator omits the Module params declaration if a key contains a control character or `*/`, which cannot be inserted safely into its PHPDoc representation. The semantic provider retains exact unusual keys. Implicit `$this->params` accesses that bypass Mago's provider dispatch require a representable patch or an application declaration.

## Investigation evidence

The source snapshot is `yii2-extensions/phpstan` revision `94504ad1b50bdfb4e5c470e898b6719140faee70`. Its 209 copied reference files were compared byte for byte with the local source checkout. Structural exploration used the repository graph, with direct source checks for its reported parser gaps. Runtime conclusions above come from actual PHPUnit, Composer and Mago commands.

The requested REA CLI was downloaded and its readiness check was run. REA 6.7.0 reported Node 25.2.0 as unsupported and the installed skill/registration as stale; its MCP tools were unavailable in the session. No REA artifact-analysis result is claimed. The supplied target is a complete PHP source repository, for which the installed REA skill directs ordinary source analysis. Global REA configuration was not changed.

The implementation follows the official [analyzer plugin API](https://mago.carthage.software/1.57.0/en/extensions/analyzer/overview/), [property provider contract](https://mago.carthage.software/1.57.0/en/extensions/analyzer/properties-and-initialization/), [vendor patch semantics](https://mago.carthage.software/1.57.0/en/guide/configuration/#patching-vendor-types), and [native corpus testing guidance](https://mago.carthage.software/1.57.0/en/extensions/development/testing-and-debugging/).
