# Installation guide

## System requirements

- [`PHP`](https://www.php.net/downloads) 8.3 or higher.
- [`Composer`](https://getcomposer.org/download/) for dependency management.

The package has no runtime dependencies and needs no framework or PHP extension beyond the PHP core.

## Installation

Install the package with [Composer](https://getcomposer.org/download/).

```bash
composer require php-forge/diff:^0.1
```

Every class lives in the `PHPForge\Diff` namespace and is loaded through the Composer autoloader. There is nothing to
register or configure: instantiate `LineDiffer` and call `diff()`, or `UnifiedDiffRenderer` and call `render()`.

```php
use PHPForge\Diff\{LineDiffer, UnifiedDiffRenderer};

$lines = (new LineDiffer())->diff("first\nsecond", "first\nthird");
$patch = (new UnifiedDiffRenderer())->render("first\nsecond\n", "first\nthird\n", 'a/notes.txt', 'b/notes.txt');
```

## Next steps

- 🧮 [Algorithms guide](algorithms.md)
- 💡 [Usage examples](examples.md)

[Back to documentation](../README.md#documentation)
