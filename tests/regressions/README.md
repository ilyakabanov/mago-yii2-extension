# Intentionally red native regressions

Run from the **repository root** after `composer install`. Verified with Mago 1.58.2,
PHP 8.5.11 and Yii 2.0.55. Every command below deliberately exits **1**. These fixtures
are outside green `composer test`: failures are not suppressed or counted as passes.
Fixtures are analyzed, not executed as application code.

## Static application flow (no Yii extension)

```sh
vendor/bin/mago --workspace tests/regressions/app-flow --config tests/regressions/app-flow/native.toml analyze --minimum-report-level note --reporting-format short
```

Expected: **five failed type confirmations**, four assignment errors and one
`impossible-condition` warning. The initial Web application control passes.
Console/base/nullable/union assignments still read as Web; the narrowed Console
branch reads as `never`. This reproduces the five original application mismatches
without Yii, generated patches or an extension host.

## Unknown dynamic method (native, optional SDK evidence)

```sh
vendor/bin/mago --workspace tests/regressions/dynamic-method --config tests/regressions/dynamic-method/native.toml analyze --minimum-report-level note --reporting-format short
vendor/bin/mago --workspace tests/regressions/dynamic-method --config tests/regressions/dynamic-method/hooks.toml analyze --minimum-report-level note --reporting-format short
```

Both report `Cannot determine the type of the first argument passed to
Mago\confirm()` for the unknown string-named call, plus `string-member-selector`.
The known static-call control confirms `int`. The optional evidence-only host adds:

```text
unknown: target=<missing>; lazy=<missing>; exact expression records=0
known:   target=int; lazy=int; exact expression records=1
```

It reports untouched SDK metadata; it does not synthesize `mixed` or alter inference.

## Inherited property generics (Yii extension)

```sh
(cd tests/regressions/property-generics && ../../../vendor/bin/mago analyze --reporting-format short --minimum-report-level warning)
```

Expected: **two failed type confirmations**. `getAttribute('value')` exposes `T`
instead of `float`, and `asArray()->one()` exposes `array{'value': T}|null` instead
of `array{'value': float}|null`. Direct `$record->value` confirms `float`.
The [property report](property-generics/README.md) details the public SDK boundary:
the receiver has no generic parameters and declaring-property metadata retains
`T`; native property access already knows the binding.

## Forwarded behavior-method generics (Yii extension)

```sh
(cd tests/regressions/behavior-generics && ../../../vendor/bin/mago analyze --minimum-report-level note --reporting-format short)
```

Expected: **one failed type confirmation**. Direct `identity($value)` on the
behavior confirms `string`; the same method forwarded through its owner retains
unresolved method template `T` instead of `string`. Inherited behavior lookup and
method precedence fixes do not resolve this generic substitution boundary.

## Negative-index append (config fixture, Yii extension)

```sh
(cd tests/fixtures/config && ../../../vendor/bin/mago --config params-append.toml analyze --reporting-format short --minimum-report-level warning)
```

Expected: **11 errors**. Three of six original configured append cases fail:

| Existing keys | PHP 8.3+ appended key | Mago key |
| --- | ---: | ---: |
| `-5` | -4 | 0 |
| `-2, -5` | -1 | 0 |
| `'name', -5` | -4 | 0 |

Each emits an undefined-index error, a `null` versus literal-int confirmation
failure and a full-shape mismatch. Negative-plus-zero, positive sparse and
string-only cases pass. The literal control `[-5 => 42]; $params[] = 43` independently
emits two errors, confirming a native engine issue rather than config normalization.
No expected key was changed to `0`. These checks validate complete key sets and
append indices, not insertion-order equality or cache invalidation.
