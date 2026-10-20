# Inherited generic property: SDK boundary

From the repository root, after `composer install`:

```sh
(cd tests/regressions/property-generics && ../../../vendor/bin/mago analyze --reporting-format short --minimum-report-level warning)
```

On Mago 1.58.2 this deliberately exits **1** with two type-confirmation failures:

- `$record->value`: **passes**, inferred as `float` by native property access.
- `$record->getAttribute('value')`: fails, inferred as unresolved `T`, expected `float`.
- `DecimalRecord::find()->asArray()->one()`: fails, inferred as `array{'value': T}|null`,
  expected `array{'value': float}|null`.

The fixture is analyzed, never required or executed. The worker loads only Composer
and the production Yii extension; no application, database, test metadata, or patch
is loaded. This intentionally failing diagnostic is outside the default green corpus.

## Public SDK evidence

A separate real-worker probe on 1.58.2 observed:

- `getMagicProperty(DecimalRecord::class, '$value')` returns null;
  `getDeclaringMagicProperty(DecimalRecord::class, '$value')` returns the parent's
  property with type `T`, whose defining entity is `TypedRecord` and constraint is `mixed`.
- `PropertyResolver::magic(..., DecimalRecord::class, 'value')->readType` is also `T`.
- The actual `getAttribute` receiver is `NamedObjectType(DecimalRecord)` with
  `parameters = null`. Public class metadata names `TypedRecord` as the direct parent,
  but does not expose the `float` inheritance argument.
- Public property lookups accept class/property strings, not a receiver type;
  the installed public SDK provides no generic-ancestor binding/projection API.

Native Mago does resolve the inheritance, as the passing control demonstrates.
The missing binding is at the exposed metadata/provider boundary, not evidence
that the PHPDoc is invalid. An SDK fix should expose receiver-instantiated property
metadata or generic ancestor bindings before production substitution is attempted.
