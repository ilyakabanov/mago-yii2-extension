# Project-specific stubs and annotations

The declaration experiments below were checked with Mago 1.57.0, PHP 8.5.11 and Yii 2.0.55 on 2026-10-19. The default and checked-in project-patch suites were repeated on Mago 1.58.2 on 2026-10-20, on both Yii 2.0.55 and Yii 22 revision `32a14a1`: results remain 634/643 and 636/643 respectively. At that upgrade checkpoint, the rejected temporary declaration experiments were not repeated. Local type declarations help with some remaining differences, but the full original suite must be checked for regressions.

## Confirmed workaround: concrete model properties

The inherited `@property T $value` in `TypedValueRecord<float>` remains unresolved in the public SDK metadata. Native direct property access does resolve `float`; SDK lookups expose declaring-owner `T`, while the actual receiver has `parameters = null` and class metadata omits the parent binding. This is a metadata/provider boundary, not a blanket inability to infer generics. See the [retained reproducer](../tests/regressions/property-generics/README.md). Repeating the concrete property type on `Measurement` fixes both `getAttribute('value')` and the `asArray()` row shape:

```php
/**
 * @extends TypedValueRecord<float>
 * @property float $value
 */
final class Measurement extends TypedValueRecord {}
```

For a model in your application's analyzed `source.paths`, put this PHPDoc on the actual class. A separate patch did not override the analyzed declaration in the isolated probe; the inline annotation passed both checks. Keep application source in `paths` so it continues to be analyzed.

For a dependency discovered through `source.includes`, an analysis-only `source.patches` file can supply the property instead. The working example is [Measurement.php](../tests/Parity/project-patches/Measurement.php). Do not autoload or require that patch at runtime. Use your model's namespace and property types; this fixture-specific declaration is not a universal Yii stub.

Reproduce the dependency workaround without changing original fixtures or expectations:

```sh
php tests/run-parity.php --project-patches --profile web
php tests/run-parity.php --project-patches
```

The Web profile improves from **526/530 to 528/530**; the complete suite improves from **634/643 to 636/643**, with no newly failing assertions. These commands still exit with failure because the other differences remain. The default parity command retains the original configuration.

## Other declarations tested

These are historical declaration experiments, not the subsequent query/behavior fixes described in [compatibility](compatibility.md). Their temporary rejected variants were not rerun after those fixes. The final unchanged-fixture runs on both Yii versions still execute all 643 comparisons, yielding 634/643 default and 636/643 with the concrete property patch, with no new mismatches and the same nine native params diagnostics. The query fixes preserve supported single-row bindings and defer declaring-owner generic override substitution to the host; they do not add complex/multi-template `asArray()` inference or resolve generic forwarded behavior methods.

| Declaration change | Result across the affected profile |
| --- | --- |
| `InvoiceQuery::one()` returns `Invoice|null` | Still 526/530. The provider's late-static row inference remains unchanged. |
| `Invoice::find()` returns `InvoiceQuery&ActiveQuery<Invoice>` | 516/530. Fixes the object-static case but introduces 11 failures, including subclass queries whose rows must remain the subclass. |
| `BaseYii::$app` declared `base\Application` | 35/39 versus 34/39. Fixes four assignment assertions, leaves the nullable assignment failing, and introduces three failures in unassigned application/narrowing checks. |
| `BaseYii::$app` declared `base\Application|null` | 25/39. Fixes all five assignment assertions but introduces 14 failures and additional nullable-access diagnostics. |

The remaining query mismatch is `$invoice::find()->one()` at adapted fixture line 178, not the class-string call at line 176, which passes. No broad declaration workaround is enabled for it.

The broad application variants were subsequently rechecked on Mago 1.58.2 with the same 35/39 and 25/39 outcomes. Broadening the application declaration does allow Mago to track compatible assignments. The cost is losing the precise configured Web type before assignment. It is therefore not a replacement for the existing generated application patch.

The unknown-name call `Invoice::$method()` still supplies no expression type to the comparison hook in all Web patch experiments. The tested declarations do not resolve it; declaring known method signatures does not specify which method an arbitrary string names.

The unsuccessful candidates were tested in temporary files and are not enabled in package or consumer configuration. The optional checked-in configuration contains only the concrete `Measurement` property workaround. These experiments demonstrate specific outcomes, not a proof that every possible stub design fails.
