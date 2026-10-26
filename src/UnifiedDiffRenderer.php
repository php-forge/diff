<?php

declare(strict_types=1);

namespace PHPForge\Diff;

use LogicException;

use function array_key_last;
use function array_map;
use function array_slice;
use function count;
use function explode;
use function implode;
use function max;
use function min;
use function preg_match;
use function preg_replace;
use function rtrim;
use function str_ends_with;
use function substr;

/**
 * Renders the output of `git diff --unified=<context>`, without its `diff --git` and `index` lines, for two texts
 * compared after normalizing their line endings to `"\n"`.
 *
 * The injected differ receives the lines without their terminators and a carriage return in place of a missing final
 * newline, so a last line without `"\n"` differs from the same line with one, as in Git, and a differ that weighs
 * indentation, such as {@see LineDiffer}, sees the layout of the original text. The rendered lines are taken back from
 * the texts by position, so any {@see DifferInterface} implementation can be injected.
 *
 * @see https://www.gnu.org/software/diffutils/manual/html_node/Unified-Format.html
 */
final readonly class UnifiedDiffRenderer
{
    /**
     * Maximum number of bytes Git keeps from a function context line, its line break included.
     */
    private const int FUNCTION_CONTEXT_LENGTH = 80;

    /**
     * Marker Git prints after a line that has no trailing newline.
     */
    private const string NO_NEWLINE_MARKER = '\\ No newline at end of file';

    /**
     * Message of the exception thrown when the differ returns a script that does not reproduce its inputs.
     */
    private const string SCRIPT_MISMATCH = 'The differ returned a script that does not reproduce its inputs.';

    /**
     * Creates a renderer.
     *
     * @param DifferInterface $differ Differ that computes the edit scripts.
     */
    public function __construct(private DifferInterface $differ = new LineDiffer()) {}

    /**
     * Renders the unified diff that transforms `$old` into `$new`.
     *
     * @param string $old Original text.
     * @param string $new Target text.
     * @param string $fromLabel Label written after `--- `.
     * @param string $toLabel Label written after `+++ `.
     * @param int $context Number of unchanged lines shown around each change; negative values count as `0`.
     *
     * @throws LogicException when the differ returns a script that does not reproduce its inputs.
     *
     * @return string Unified diff without a trailing newline, or an empty string when both texts are identical after
     * line-ending normalization.
     */
    public function render(string $old, string $new, string $fromLabel, string $toLabel, int $context = 3): string
    {
        $old = self::normalizeLineEndings($old);
        $new = self::normalizeLineEndings($new);

        if ($old === $new) {
            return '';
        }

        $oldLines = self::splitLines($old);
        $edits = $this->edits($old, $new, $oldLines, self::splitLines($new));

        // A context longer than the script shows every line already, and capping it keeps the hunk bounds integers.
        $context = min(max(0, $context), count($edits));

        $output = ["--- {$fromLabel}", "+++ {$toLabel}"];

        foreach (self::hunks($edits, $context) as [$first, $last, $oldBefore, $newBefore]) {
            $start = max(0, $first - $context);
            $leading = $first - $start;

            self::renderHunk(
                $output,
                array_slice($edits, $start, $last + $context + 1 - $start),
                $oldLines,
                $oldBefore - $leading,
                $newBefore - $leading,
            );
        }

        return implode("\n", $output);
    }

    /**
     * Returns the input handed to the differ for a non-empty normalized text: its lines without their terminators,
     * with a carriage return standing in for a missing final newline.
     *
     * Normalized text holds no carriage return, so the marker keeps a last line without a newline different from the
     * same line with one, as in Git, while the indentation a differ may weigh stays that of the original line.
     *
     * @param string $text Non-empty text with `"\n"` line endings.
     */
    private static function differInput(string $text): string
    {
        return str_ends_with($text, "\n") ? substr($text, 0, -1) : "{$text}\r";
    }

    /**
     * Returns the edits that transform `$old` into `$new`, each with the number of old and new lines before it.
     *
     * @param string $old Normalized original text.
     * @param string $new Normalized target text.
     * @param list<string> $oldLines Old lines, each keeping its `"\n"` terminator when present.
     * @param list<string> $newLines New lines, each keeping its `"\n"` terminator when present.
     *
     * @throws LogicException when the differ returns a script that does not reproduce its inputs.
     *
     * @return list<array{DiffOp, string, int, int}> Operation, line, and number of old and new lines before the edit.
     */
    private function edits(string $old, string $new, array $oldLines, array $newLines): array
    {
        // An empty text has no lines, which the differ cannot express: it would see one empty line.
        $script = match ('') {
            $old => array_map(static fn(): DiffLine => new DiffLine(DiffOp::Add, ''), $newLines),
            $new => array_map(static fn(): DiffLine => new DiffLine(DiffOp::Remove, ''), $oldLines),
            default => $this->differ->diff(self::differInput($old), self::differInput($new)),
        };

        $edits = [];
        $oldBefore = 0;
        $newBefore = 0;

        foreach ($script as $diffLine) {
            $op = $diffLine->op;
            $edits[] = [
                $op,
                $op === DiffOp::Add
                    ? $newLines[$newBefore] ?? throw new LogicException(self::SCRIPT_MISMATCH)
                    : $oldLines[$oldBefore] ?? throw new LogicException(self::SCRIPT_MISMATCH),
                $oldBefore,
                $newBefore,
            ];

            if ($op !== DiffOp::Add) {
                $oldBefore++;
            }

            if ($op !== DiffOp::Remove) {
                $newBefore++;
            }
        }

        if ($oldBefore !== count($oldLines) || $newBefore !== count($newLines)) {
            throw new LogicException(
                self::SCRIPT_MISMATCH,
            );
        }

        return $edits;
    }

    /**
     * Formats one side of a hunk range using Git's collapsed notation.
     *
     * @param int $before Number of lines on this side that precede the hunk.
     * @param int $lines Number of lines on this side inside the hunk.
     *
     * @return string Range such as `3,4`, `3` for a single line, or `2,0` for an empty range after line 2.
     */
    private static function formatRange(int $before, int $lines): string
    {
        $startLine = $lines === 0 ? $before : $before + 1;

        return $lines === 1 ? (string) $startLine : "{$startLine},{$lines}";
    }

    /**
     * Returns Git's default function context for a hunk that starts after `$oldBefore` old lines: the nearest previous
     * line that starts with a letter, `_` or `$`.
     *
     * @param list<string> $oldLines Old lines, each keeping its `"\n"` terminator when present.
     * @param int $oldBefore Number of old lines that precede the hunk.
     *
     * @return string Function context, or an empty string when no previous line qualifies.
     */
    private static function functionContext(array $oldLines, int $oldBefore): string
    {
        for ($index = $oldBefore - 1; isset($oldLines[$index]); $index--) {
            if (preg_match('/^[A-Za-z_$]/', $oldLines[$index]) === 1) {
                return rtrim(substr($oldLines[$index], 0, self::FUNCTION_CONTEXT_LENGTH), " \t\n\r");
            }
        }

        return '';
    }

    /**
     * Groups the changes of an edit script into hunks, joining changes separated by at most `2 * $context` unchanged
     * lines.
     *
     * @param list<array{DiffOp, string, int, int}> $edits Edit script.
     * @param int $context Number of unchanged lines shown around each change.
     *
     * @return list<array{int, int, int, int}> Indexes of the first and last change of each hunk, and the number of old
     * and new lines before its first change.
     */
    private static function hunks(array $edits, int $context): array
    {
        $hunks = [];

        foreach ($edits as $index => [$op, , $oldBefore, $newBefore]) {
            if ($op === DiffOp::Context) {
                continue;
            }

            $last = array_key_last($hunks);

            if ($last !== null && $index - $hunks[$last][1] <= 2 * $context + 1) {
                $hunks[$last][1] = $index;
            } else {
                $hunks[] = [$index, $index, $oldBefore, $newBefore];
            }
        }

        return $hunks;
    }

    /**
     * Normalizes `"\r\n"` and `"\r"` line endings to `"\n"`.
     */
    private static function normalizeLineEndings(string $text): string
    {
        return (string) preg_replace('/\r\n|\r/', "\n", $text);
    }

    /**
     * Appends the header and lines of one hunk to `$output`.
     *
     * @param list<string> $output Rendered lines, appended in place.
     * @param list<array{DiffOp, string, int, int}> $edits Edits of the hunk.
     * @param list<string> $oldLines Old lines, each keeping its `"\n"` terminator when present.
     * @param int $oldBefore Number of old lines that precede the hunk.
     * @param int $newBefore Number of new lines that precede the hunk.
     */
    private static function renderHunk(
        array &$output,
        array $edits,
        array $oldLines,
        int $oldBefore,
        int $newBefore,
    ): void {
        $oldCount = 0;
        $newCount = 0;
        $body = [];

        foreach ($edits as [$op, $line]) {
            [$prefix, $oldCount, $newCount] = match ($op) {
                DiffOp::Context => [' ', $oldCount + 1, $newCount + 1],
                DiffOp::Remove => ['-', $oldCount + 1, $newCount],
                DiffOp::Add => ['+', $oldCount, $newCount + 1],
            };

            if (str_ends_with($line, "\n")) {
                $body[] = $prefix . substr($line, 0, -1);
            } else {
                $body[] = $prefix . $line;
                $body[] = self::NO_NEWLINE_MARKER;
            }
        }

        $header = '@@ -'
            . self::formatRange($oldBefore, $oldCount)
            . ' +'
            . self::formatRange($newBefore, $newCount)
            . ' @@';
        $function = self::functionContext($oldLines, $oldBefore);

        $output[] = $function === '' ? $header : "{$header} {$function}";

        foreach ($body as $line) {
            $output[] = $line;
        }
    }

    /**
     * Splits normalized text into lines that keep their `"\n"` terminator, so a last line without a newline differs
     * from the same line with one.
     *
     * @param string $text Text with `"\n"` line endings.
     *
     * @return list<string> Lines; empty for an empty text.
     */
    private static function splitLines(string $text): array
    {
        $parts = explode("\n", $text);
        $last = array_key_last($parts);

        $lines = [];

        foreach ($parts as $index => $part) {
            if ($index !== $last) {
                $lines[] = "{$part}\n";
            } elseif ($part !== '') {
                $lines[] = $part;
            }
        }

        return $lines;
    }
}
