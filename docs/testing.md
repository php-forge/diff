# Testing

This package provides a consistent set of [Composer](https://getcomposer.org/) scripts for local validation.

Tool references:

- [Actionlint](https://github.com/rhysd/actionlint) for GitHub Actions workflow linting.
- [Codespell](https://github.com/codespell-project/codespell) for spelling checks.
- [Composer Require Checker](https://github.com/maglnet/ComposerRequireChecker) for dependency definition checks.
- [Easy Coding Standard (ECS)](https://github.com/easy-coding-standard/easy-coding-standard) for coding standards.
- [EditorConfig Checker](https://github.com/editorconfig-checker/editorconfig-checker) for EditorConfig compliance.
- [Gitleaks](https://github.com/gitleaks/gitleaks) for secret scanning.
- [Infection](https://infection.github.io/) for mutation testing.
- [Markdownlint-cli2](https://github.com/DavidAnson/markdownlint-cli2) for Markdown linting.
- [PHPStan](https://phpstan.org/) for static analysis.
- [Prettier](https://prettier.io/) for Markdown and YAML formatting checks.
- [Rector](https://github.com/rectorphp/rector) for automated refactoring.
- [Testo](https://github.com/php-testo/testo) for unit tests and benchmarks.
- [Yamllint](https://github.com/adrienverge/yamllint) for YAML linting.
- [Zizmor](https://github.com/zizmorcore/zizmor) for GitHub Actions security audits.

The Testo configuration lives in `testo.php`: the `Unit` suite reads `tests/Unit` and the `Bench` suite reads
`tests/Bench`. The Git compatibility cases live in `tests/Fixtures/git-cases.json`: each one stores two line lists and
the edit operations of `git diff --no-index -U100000` run without user or system configuration
(`GIT_CONFIG_GLOBAL=/dev/null GIT_CONFIG_NOSYSTEM=1`), since a configured `diff.algorithm` changes the result.

## Automated refactoring (Rector)

Run Rector to apply automated code refactoring. This command modifies files.

```bash
composer rector
```

Preview the refactoring without modifying files.

```bash
composer rector-check
```

## Benchmarks (Testo)

Run the benchmarks that measure `LineDiffer` on its own and behind `UnifiedDiffRenderer` on the same generated input.

```bash
composer bench
```

The script runs `testo --type=bench`. Timings depend on the machine and the PHP version, so compare results from the
same run.

## Coding standards (ECS)

Check coding standards without modifying files.

```bash
composer ecs-check
```

Apply coding standard fixes. Review the changes before keeping them.

```bash
composer ecs
```

## Dependency definition check

Verify that runtime dependencies are correctly declared in `composer.json`.

```bash
composer check-dependencies
```

## Mutation testing (Infection)

Run mutation testing with PHPStan static analysis enabled. Infection runs the Testo suites through
`testo/bridge-infection`. This is the command CI runs.

```bash
composer mutation-static
```

Run mutation testing without static analysis.

```bash
composer mutation
```

Both runs reach the required score without ignored mutants. Thresholds, thread count and memory options live in
`infection.json5`; the package requires a 100 % mutation score indicator (MSI). CI enforces the same thresholds.

## Repository quality checks

Make the CLI tools used by the shared quality workflow available on `PATH`: actionlint with its ShellCheck helper,
codespell, EditorConfig Checker, markdownlint-cli2, Prettier and yamllint. Run all checks:

```bash
composer quality
```

Each tool also has its own script:

```bash
composer actionlint
composer codespell
composer editorconfig-checker
composer markdownlint
composer prettier
composer yamllint
```

The linter configuration lives in `.github/linters`, and the Prettier configuration in `.prettierrc.json` and
`.prettierignore`. CI uses the same files.

## Static analysis (PHPStan)

Run static analysis at level max.

```bash
composer static
```

## Unit tests (Testo)

Run the test suites without the benchmarks.

```bash
composer tests
```

The script runs `testo --type=!bench`. CI runs the same command on PHP 8.3, 8.4 and 8.5, on Ubuntu and Windows, and
uploads the coverage to Codecov.

### Running one test

Filter the tests by class or method name (the match is case-sensitive), and print a single JSON summary with the failed
tests instead of the terminal report.

```bash
vendor/bin/testo --json --filter=LineDifferTest
vendor/bin/testo --json --filter=matchesGitEditOperations
```

### Code coverage

Collect coverage with PCOV or Xdebug enabled and write a Clover XML report.

```bash
vendor/bin/testo --coverage-clover=runtime/clover.xml
```

## Workflow security checks

Make zizmor and Gitleaks available on `PATH`. Audit the current workflow files with the same pedantic persona used by
the shared security workflow:

```bash
composer zizmor
```

Scan Git history for secrets with the repository's Gitleaks configuration:

```bash
composer gitleaks
```

Run both checks. Findings make the command fail; inspect them rather than disabling the checks.

```bash
composer security
```

Zizmor needs GitHub API access for its online audits. If the tool runs offline, report that limit separately. To request
an offline audit explicitly:

```bash
composer zizmor -- --offline
```

Report hosted CI execution and its PHP/OS matrix separately from these local commands. Keep generated reports in ignored
`runtime/` or `internal/`.

## Passing extra arguments

Composer scripts support forwarding additional arguments using `--`.

Run the Testo suites with a Clover coverage report.

```bash
composer tests -- --coverage-clover=runtime/clover.xml
```

Run only the tests of one suite.

```bash
composer tests -- --suite=Unit
```

Run PHPStan with a different memory limit.

```bash
composer static -- --memory-limit=512M
```

## Next steps

- 📚 [Installation guide](installation.md)
- 🧮 [Algorithms guide](algorithms.md)
- 💡 [Usage examples](examples.md)

[Back to documentation](../README.md#documentation)
