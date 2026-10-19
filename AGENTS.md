# Repository Guidelines

## Project Structure & Module Organization

- `src/Yii2Extension.php` is the consumer registration entrypoint; `src/Analyzer/` contains the plugin and semantic providers.
- `src/ServiceMap.php`, `ParamsTypeBuilder.php`, and `StubGenerator.php` handle configuration normalization, params shapes, and analysis patches.
- `bin/mago-yii2-stubs` exposes patch generation through Composer.
- `tests/Unit/` contains PHPUnit tests; `tests/corpus/` exercises a real Mago worker; `tests/consumer/` verifies Composer integration.
- `tests/reference/` preserves the original PHPStan test snapshot. `tests/Parity/` contains adapted fixtures, their generator, and comparison metadata.
- `docs/` documents testing and compatibility; `.github/workflows/tests.yml` defines the PHP/Yii matrix.

## Build, Test, and Development Commands

Run commands from the repository root:

- `composer install` installs PHP dependencies; the first Mago invocation downloads its native binary.
- `composer test` runs PHPUnit and the native worker corpus.
- `composer test:unit` or `composer test:corpus` runs either suite separately.
- `composer analyze` checks package source with Mago.
- `composer format:check` verifies formatting; `vendor/bin/mago fmt` applies it.
- `composer validate --strict` checks package metadata.
- `composer test:parity-harness` validates the comparison harness; `composer test:parity` compares original expectations and currently reports remaining failures.

## Coding Style & Naming Conventions

Use strict PHP types, four-space indentation, and a 120-column formatting width. Follow PSR-4 under `yii2\extensions\mago`; use PascalCase class/file names and descriptive provider names such as `HeaderProvider`. Keep providers narrowly targeted and return `null` when inference is unsupported, preserving native analysis. Use the public Mago SDK in production code.

## Testing Guidelines

Name PHPUnit files `*Test.php` under `tests/Unit/`. Add regression cases for inference changes and verify dispatch through the real corpus. Use typed consumers, negative `@mago-expect` cases, and exact type confirmations. Preserve reference files and original expected types; regenerate adapted fixtures through their generator. Before changing inference or parity handling, read `docs/testing.md` and `docs/compatibility.md`. Report remaining mismatches explicitly.

## Commit & Pull Request Guidelines

No commit history exists yet. Use concise imperative subjects, for example `Fix nullable service locator inference`. Keep changes focused. PR descriptions should explain behavior, reference relevant issues, list validation commands/results, and identify compatibility changes. Include a minimal reproducer for inference fixes.

## Configuration & Patch Safety

Analysis configuration executes inside the worker: keep it free of application startup, database access, and printed output. Worker stdout carries protocol frames. Generated patches belong in Mago's `source.patches`, outside runtime autoloading. Regenerate patches after application/params changes and restart running analysis after configuration changes.
