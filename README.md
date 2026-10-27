<!-- markdownlint-disable MD041 -->
<p align="center">
    <a href="https://github.com/php-forge/diff" target="_blank">
      <img src="https://avatars.githubusercontent.com/u/103309199?s=400&u=ca3561c692f53ed7eb290d3bb226a2828741606f&v=4" width="30%" alt="PHP Forge">
    </a>
    <h1 align="center">Diff</h1>
    <br>
</p>
<!-- markdownlint-enable MD041 -->

<p align="center">
    <a href="https://github.com/php-forge/diff/actions/workflows/build.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/php-forge/diff/build.yml?style=for-the-badge&label=Testo&logo=github" alt="Testo">
    </a>
    <a href="https://dashboard.stryker-mutator.io/reports/github.com/php-forge/diff/main" target="_blank">
        <img src="https://img.shields.io/endpoint?style=for-the-badge&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fphp-forge%2Fdiff%2Fmain" alt="Mutation Testing">
    </a>
    <a href="https://github.com/php-forge/diff/actions/workflows/static.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/php-forge/diff/static.yml?style=for-the-badge&label=PHPStan&logo=github" alt="PHPStan">
    </a>
    <a href="https://github.com/php-forge/diff/actions/workflows/security.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/php-forge/diff/security.yml?style=for-the-badge&label=Security&logo=github" alt="Security">
    </a>
</p>

<p align="center">
    <strong>Git-compatible line diff for PHP</strong><br>
    <em>A port of Git's default diff, indent heuristic included, plus a unified diff renderer, with no runtime dependencies.</em>
</p>

## Features

<picture>
    <source media="(min-width: 768px)" srcset="./docs/svgs/features.svg">
    <img src="./docs/svgs/features-mobile.svg" alt="Feature Overview" style="width: 100%;">
</picture>

## Installation

```bash
composer require php-forge/diff:^0.1
```

## Quick start

`LineDiffer` implements `PHPForge\Diff\DifferInterface`. `diff()` splits both strings on `"\n"` and returns an ordered
list of `DiffLine` objects, each tagged with a `DiffOp`:

- `DiffOp::Remove`: the line exists only in `$old`.
- `DiffOp::Add`: the line exists only in `$new`.
- `DiffOp::Context`: the line exists on both sides.

Every input line appears exactly once, so dropping the `Add` lines reconstructs `$old` and dropping the `Remove` lines
reconstructs `$new`.

```php
use PHPForge\Diff\{DiffOp, LineDiffer};

$old = "apple\nbanana\ncherry";
$new = "apple\nblueberry\ncherry";

foreach ((new LineDiffer())->diff($old, $new) as $line) {
    echo match ($line->op) {
        DiffOp::Context => ' ',
        DiffOp::Remove => '-',
        DiffOp::Add => '+',
    } . $line->line . "\n";
}
```

```text
 apple
-banana
+blueberry
 cherry
```

`UnifiedDiffRenderer` turns two texts into a unified diff in Git's format, hunk headers and function context included,
without the `diff --git` and `index` lines.

```php
use PHPForge\Diff\UnifiedDiffRenderer;

$old = "apple\nbanana\ncherry\n";
$new = "apple\nblueberry\ncherry\n";

echo (new UnifiedDiffRenderer())->render($old, $new, 'a/fruit.txt', 'b/fruit.txt');
```

```text
--- a/fruit.txt
+++ b/fruit.txt
@@ -1,3 +1,3 @@
 apple
-banana
+blueberry
 cherry
```

## What you get

| Class                 | Result                                                      | Cost                                         |
| --------------------- | ----------------------------------------------------------- | -------------------------------------------- |
| `LineDiffer`          | The edit script of `git diff` with its default settings     | O((N + M) · D) time, linear memory           |
| `UnifiedDiffRenderer` | The text of `git diff --unified=<n>` from the `---` line on | The injected differ plus one pass over edits |

N and M are the line counts of `$old` and `$new`, and D is the edit distance. Like Git, `LineDiffer` favours readable
scripts over the shortest one, so its edit count is not always minimal. Unlike Git, it has no cost limit, so on very
expensive inputs its script can differ from Git's. See the [algorithms guide](docs/algorithms.md) for the pipeline and
its trade-offs.

## Documentation

- 📚 [Installation guide](docs/installation.md)
- 🧮 [Algorithms guide](docs/algorithms.md)
- 💡 [Usage examples](docs/examples.md)
- 🧪 [Testing guide](docs/testing.md)
- 🤖 [AI assistance](docs/agents.md)

## AI assistance

AI agents: read the [documentation index](https://yii2-framework.github.io/docs/llms.txt) and fetch the Markdown version
of a page by appending `index.md` to its URL. The
[AI agents guide](https://yii2-framework.github.io/docs/project/ai-agents/) covers skill installation, client setup and
verification.

- 🤖 [`php-forge-diff` skill](docs/agents.md): Line edit scripts and unified diffs.

If hosted documentation does not match the installed revision or is unavailable, use the
[repository guides](#documentation), the [skill source](skills/php-forge-diff/SKILL.md), the source PHPDoc and the
[changelog](CHANGELOG.md).

## Package information

[![PHP](https://img.shields.io/badge/%3E%3D8.3-777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/releases/8.3/en.php)
[![Latest Stable Version](https://img.shields.io/packagist/v/php-forge/diff.svg?style=for-the-badge&logo=packagist&logoColor=white&label=Stable)](https://packagist.org/packages/php-forge/diff)
[![Total Downloads](https://img.shields.io/packagist/dt/php-forge/diff.svg?style=for-the-badge&logo=composer&logoColor=white&label=Downloads)](https://packagist.org/packages/php-forge/diff)

## Project status

[![Codecov](https://img.shields.io/codecov/c/github/php-forge/diff.svg?style=for-the-badge&logo=codecov&logoColor=white&label=Coverage)](https://codecov.io/github/php-forge/diff)
[![PHPStan Level Max](https://img.shields.io/badge/PHPStan-Level%20Max-4F5D95.svg?style=for-the-badge&logo=github&logoColor=white)](https://github.com/php-forge/diff/actions/workflows/static.yml)
[![Quality](https://img.shields.io/github/actions/workflow/status/php-forge/diff/quality.yml?style=for-the-badge&label=Quality&logo=github)](https://github.com/php-forge/diff/actions/workflows/quality.yml)

## Built with AI

[![Built with Claude Code](https://img.shields.io/badge/Built%20with-Claude%20Code-D97757.svg?style=for-the-badge&logo=claude&logoColor=white)](https://claude.com/claude-code)
[![Built with Codex](https://img.shields.io/badge/Built%20with-Codex-10A37F.svg?style=for-the-badge&logo=openaigym&logoColor=white)](https://openai.com/codex)
[![Reviewed by CodeRabbit](https://img.shields.io/badge/Reviewed%20by-CodeRabbit-FF570A.svg?style=for-the-badge&logo=coderabbit&logoColor=white)](https://www.coderabbit.ai)

## Our social networks

[![Follow on X](https://img.shields.io/badge/-Follow%20on%20X-1DA1F2.svg?style=for-the-badge&logo=x&logoColor=white&labelColor=000000)](https://x.com/Terabytesoftw)
[![Follow on Facebook](https://img.shields.io/badge/-Follow%20on%20Facebook-1877F2.svg?style=for-the-badge&logo=facebook&logoColor=white&labelColor=000000)](https://www.facebook.com/wilmer.arambula.9)

## License

[![License](https://img.shields.io/badge/License-BSD--3--Clause-brightgreen.svg?style=for-the-badge&logo=opensourceinitiative&logoColor=white&labelColor=555555)](LICENSE)
