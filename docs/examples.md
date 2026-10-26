# Usage examples

Each section shows one use case. The edit scripts come from `LineDiffer`; any other `DifferInterface` implementation can
replace it without other changes.

## Basic diff

`diff()` returns an ordered list of `DiffLine` objects. Each one exposes the operation in `$op` and the line text,
without its line break, in `$line`.

```php
use PHPForge\Diff\{DiffOp, LineDiffer};

$old = "alpha\nbeta\ngamma";
$new = "alpha\ngamma\ndelta";

$removed = 0;
$added = 0;

foreach ((new LineDiffer())->diff($old, $new) as $diffLine) {
    if ($diffLine->op === DiffOp::Remove) {
        $removed++;
    } elseif ($diffLine->op === DiffOp::Add) {
        $added++;
    }
}

echo "{$removed} removed, {$added} added\n"; // 1 removed, 1 added
```

## Rendering a unified diff

`UnifiedDiffRenderer` returns the text `git diff` prints for two files, from the `---` line on.

```php
use PHPForge\Diff\UnifiedDiffRenderer;

$old = "[*]\nindent_style = space\nindent_size = 4\n";
$new = "[*]\nindent_style = space\nindent_size = 2\n";

echo (new UnifiedDiffRenderer())->render($old, $new, 'a/.editorconfig', 'b/.editorconfig') . "\n";
```

```text
--- a/.editorconfig
+++ b/.editorconfig
@@ -1,3 +1,3 @@
 [*]
 indent_style = space
-indent_size = 4
+indent_size = 2
```

The fifth argument sets the number of context lines, `3` by default. An empty string means both texts are equal once
their line endings are normalized.

## Reconstructing a side

Every input line appears exactly once, so filtering out one operation and joining the rest with `"\n"` gives back the
corresponding input.

```php
use PHPForge\Diff\{DiffLine, DiffOp, LineDiffer};

$old = "one\ntwo\nthree";
$new = "one\n2\nthree\nfour";

$lines = (new LineDiffer())->diff($old, $new);

$side = static fn(DiffOp $skip): string => implode(
    "\n",
    array_map(
        static fn(DiffLine $diffLine): string => $diffLine->line,
        array_filter($lines, static fn(DiffLine $diffLine): bool => $diffLine->op !== $skip),
    ),
);

var_dump($side(DiffOp::Add) === $old);    // bool(true)
var_dump($side(DiffOp::Remove) === $new); // bool(true)
```

## Wrapping a custom differ

Implement `DifferInterface` to add behaviour around an existing differ. This decorator normalizes Windows line endings
before delegating, so `"\r\n"` and `"\n"` inputs compare equal. The reconstructed sides then use `"\n"` line breaks.

```php
use PHPForge\Diff\{DiffLine, DifferInterface, LineDiffer};

final readonly class LineEndingNormalizingDiffer implements DifferInterface
{
    public function __construct(
        private DifferInterface $inner = new LineDiffer(),
    ) {}

    /**
     * @return list<DiffLine>
     */
    public function diff(string $old, string $new): array
    {
        return $this->inner->diff(
            str_replace("\r\n", "\n", $old),
            str_replace("\r\n", "\n", $new),
        );
    }
}

$lines = (new LineEndingNormalizingDiffer())->diff("a\r\nb\r\n", "a\nc\n");
```

A custom differ can also be passed to `UnifiedDiffRenderer`, for example `new UnifiedDiffRenderer($custom)`, to render
its edit scripts as a unified diff.

## Next steps

- 🧮 [Algorithms guide](algorithms.md)
- 🧪 [Testing guide](testing.md)

[Back to documentation](../README.md#documentation)
