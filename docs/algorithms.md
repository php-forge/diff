# Algorithms guide

`LineDiffer` implements `PHPForge\Diff\DifferInterface`, whose contract any other differ follows too:

- Both strings are split on `"\n"`. A trailing `"\n"` therefore yields a final empty line, and an empty string is a
  single empty line.
- The result is an ordered `list<DiffLine>`. `DiffOp::Remove` marks a line found only in `$old`, `DiffOp::Add` a line
  found only in `$new`, and `DiffOp::Context` a line kept on both sides.
- Every input line appears exactly once. Dropping the `Add` lines reconstructs `$old`; dropping the `Remove` lines
  reconstructs `$new`.

In the notes below, N and M are the line counts of `$old` and `$new`, and D is the edit distance between them.

## LineDiffer

A port of the line diff Git runs by default (`git diff` without options): the Myers algorithm of Git's `xdiff` library,
followed by its compaction pass and the indent heuristic. For the same lines it returns the same edit script as Git,
line for line.

- **Result:** the edit script of `git diff`. Within each change the removals come before the additions.
- **Complexity:** O((N + M) · D) time and linear memory. Two inputs of a few thousand lines that differ everywhere are
  diffed in milliseconds, because lines without a counterpart never reach the alignment.
- **Minimality:** not guaranteed. Like Git, it discards over-popular lines before the alignment, so on some inputs the
  script holds a few more edits than the shortest one, in exchange for an alignment that does not pair up unrelated
  repeated lines.
- **Constructor options:** none.

```php
use PHPForge\Diff\LineDiffer;

$lines = (new LineDiffer())->diff($old, $new);
```

### Pipeline

1. **Interning.** Every distinct line is mapped to an integer, so the later stages compare integers, byte for byte.
2. **Trimming.** The common head and tail are kept as context and leave the problem.
3. **Cleanup.** Each remaining line is classified by how often it occurs on the other side (`LineMatch`). Lines with no
   counterpart are changed at once. A line that occurs at least about √N times on the other side (the bound stops at
   `1024`) is multi-matched, and it is discarded too when it sits inside a run of unmatched lines, so blank lines and
   lone braces do not anchor the alignment. The limits live in `MultimatchCleanup`.
4. **Alignment.** The retained lines are aligned with the linear-space refinement of Myers' algorithm: the search runs
   forwards and backwards at once, the "middle snake" where both meet splits the problem in two, and both halves are
   solved the same way.
5. **Compaction.** Every group of changed lines that can slide over identical lines is moved to its canonical place: in
   line with a change of the other side when possible, otherwise where the indent heuristic scores best. The heuristic
   rates each candidate split by the indentation and the blank lines around it, so a new method or block is shown as a
   whole instead of starting in the middle of its neighbour. Its tuning values, the ones Git uses, live in
   `IndentHeuristic`.

### Difference from Git

Git limits the cost of very expensive diffs: once one step of its search reaches an edit cost of 256, it falls back to
heuristics that stop early. `LineDiffer` does not port those limits and always completes the search, so on such inputs
its script can differ from Git's. Everything else, including the cleanup, the compaction and the indent heuristic,
follows Git.

## UnifiedDiffRenderer

Renders the text of `git diff --unified=<n>`, without the `diff --git` and `index` lines, from two strings.

- **Line endings:** `"\r\n"` and `"\r"` are normalized to `"\n"` first, so texts that differ only in their line endings
  render an empty string.
- **Output:** `---` and `+++` lines with the labels as given, then one hunk per group of changes, with Git's collapsed
  ranges (`@@ -3 +3,2 @@`), Git's default function context after the range (the nearest previous line that starts with a
  letter, `_` or `$`, cut to 80 bytes) and the `\ No newline at end of file` marker. The result has no trailing newline.
- **Context:** `$context` unchanged lines around each change, `3` by default; changes closer than twice that share a
  hunk, and negative values count as `0`.
- **Constructor options:** `DifferInterface $differ = new LineDiffer()`, the differ that computes the edit scripts.

The differ sees the lines of the normalized texts without their terminators. A last line without a newline gains a
carriage return, so it differs from the same line with one, as in Git, while its indentation stays intact for the indent
heuristic. The rendered lines are taken back from the texts by position, so any `DifferInterface` implementation can be
injected.

```php
use PHPForge\Diff\UnifiedDiffRenderer;

echo (new UnifiedDiffRenderer())->render($old, $new, 'a/config.yml', 'b/config.yml');

// One line of context instead of three.
echo (new UnifiedDiffRenderer())->render($old, $new, 'a/config.yml', 'b/config.yml', 1);
```

## Measurements

Run `composer bench` to measure `LineDiffer` on its own and behind `UnifiedDiffRenderer` on your own machine; see the
[testing guide](testing.md#benchmarks-testo).

## Next steps

- 💡 [Usage examples](examples.md)
- 🧪 [Testing guide](testing.md)

[Back to documentation](../README.md#documentation)
