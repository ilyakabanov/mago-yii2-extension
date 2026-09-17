# Differential audit

`just audit` records compatibility; it does not fix differences or change the Yii2 preset.

The command installs PHPCS 4.0.4, Yii2 Coding Standards 3.0.2, and Mago 1.47.1 in `/tmp`. It discovers diagnostics from the PHPCS upstream unit fixtures and the project's existing contract fixtures, then checks one representative file for every observed concrete diagnostic code. The manifest prefers focused project contracts where they exist and otherwise uses the sniff's upstream fixture; formatter parse failures are recorded explicitly.

Classifications in `baseline.json` mean:

- `lint`: the configured Mago rule reports every observed violation at the same file, line, column, type, and severity;
- `formatter`: Mago removes the PHPCS diagnostic and a second format is idempotent;
- `different`: Mago reports or changes something, but does not meet the exact lint or formatter contract;
- `unsupported`: no matching lint result was found and formatting did not remove the diagnostic.

Recorded `different` and `unsupported` results are successful audit outcomes. The command fails only when the pinned tool versions expose a new, removed, or changed result. After reviewing an intentional change, regenerate the record with:

```shell
php tests/Audit/run.php --update
```

The audit covers diagnostics exercised by the pinned fixture corpus. It does not claim that unreachable or unexercised branches inside PHPCS sniff implementations were discovered.
