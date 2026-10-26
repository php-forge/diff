<?php

declare(strict_types=1);

namespace PHPForge\Diff;

use Override;

use function array_count_values;
use function array_fill;
use function array_map;
use function array_pop;
use function array_push;
use function array_slice;
use function count;
use function explode;
use function max;
use function min;
use function strlen;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Computes line edit scripts identical to the ones of `git diff` with default settings.
 *
 * Ports the pipeline of Git's `xdiff` library: lines are interned, the common head and tail are trimmed, lines without
 * a counterpart and runs of over-popular lines are discarded before the alignment, the remaining lines are aligned with
 * the linear-space middle-snake refinement of Myers' algorithm, and every group of changes is slid to its canonical
 * position, scored by the indent heuristic. The cost-limiting heuristics Git applies to very expensive diffs are not
 * ported, so the alignment always runs to completion. Memory stays linear in the input size; because discarded lines
 * never anchor the alignment, the edit script is not always the shortest one.
 *
 * @see https://doi.org/10.1007/BF01840446 Eugene W. Myers, "An O(ND) Difference Algorithm and Its Variations".
 * @see https://github.com/git/git/tree/master/xdiff Git's `xdiff` library.
 */
final class LineDiffer implements DifferInterface
{
    /**
     * Returns the edit script that `git diff` produces to transform `$old` into `$new`.
     *
     * @param string $old Original input, split into lines on `"\n"`.
     * @param string $new Target input, split into lines on `"\n"`.
     *
     * @return list<DiffLine> Edit script; its `Context` and `Remove` lines in order reproduce the lines of `$old`, and
     * its `Context` and `Add` lines in order reproduce the lines of `$new`.
     */
    #[Override]
    public function diff(string $old, string $new): array
    {
        $oldLines = explode("\n", $old);
        $newLines = explode("\n", $new);

        $ids = [];
        $a = [];
        $b = [];

        foreach ($oldLines as $line) {
            $a[] = $ids[$line] ??= count($ids);
        }

        foreach ($newLines as $line) {
            $b[] = $ids[$line] ??= count($ids);
        }

        $changedA = array_fill(0, count($a), false);
        $changedB = array_fill(0, count($b), false);

        self::markChanges($a, $b, $changedA, $changedB);

        self::compact($a, self::indents($oldLines), $changedA, $changedB);
        self::compact($b, self::indents($newLines), $changedB, $changedA);

        return self::buildScript($oldLines, $newLines, $changedA, $changedB);
    }

    /**
     * Returns the sum of two indent-heuristic scores.
     *
     * @param array{int, int} $first Effective indent and penalty of the first split.
     * @param array{int, int} $second Effective indent and penalty of the second split.
     *
     * @return array{int, int} Combined effective indent and penalty.
     */
    private static function addScores(array $first, array $second): array
    {
        return [$first[0] + $second[0], $first[1] + $second[1]];
    }


    /**
     * Returns the end position of a slidable group preferred by Git's indent heuristic.
     *
     * Every candidate end position is scored by the splits before and after the group; ties go to the largest end
     * position.
     *
     * @param list<int> $indents Indentation of each line, `IndentHeuristic::BLANK` for whitespace-only lines.
     * @param int $earliestEnd Smallest end position the group can slide to.
     * @param int $end Largest end position the group can slide to, which is its current one.
     * @param int $size Number of lines in the group.
     */
    private static function bestEnd(array $indents, int $earliestEnd, int $end, int $size): int
    {
        $best = null;
        $shift = max($earliestEnd, $end - $size - 1, $end - IndentHeuristic::MAX_SLIDING);

        do {
            $score = self::addScores(self::scoreSplit($indents, $shift), self::scoreSplit($indents, $shift - $size));
            $best = $best === null || self::compareScores($score, $best[1]) <= 0 ? [$shift, $score] : $best;
        } while (++$shift <= $end);

        return $best[0];
    }

    /**
     * Returns Git's `xdl_bogosqrt()` approximation of the square root of `$value`.
     */
    private static function bogoSqrt(int $value): int
    {
        $root = 1;

        for (; $value > 0; $value >>= 2) {
            $root <<= 1;
        }

        return $root;
    }

    /**
     * Builds the edit script from the per-line change flags, emitting the removals of each change before its additions.
     *
     * The additions are grouped by the number of unchanged new lines before them, which pairs each group with the
     * unchanged old line it precedes, since unchanged lines pair up in order.
     *
     * @param list<string> $old Lines of the original input.
     * @param list<string> $new Lines of the target input.
     * @param array<int, bool> $changedA Change flags of the old lines.
     * @param array<int, bool> $changedB Change flags of the new lines.
     *
     * @return list<DiffLine> Edit script.
     */
    private static function buildScript(array $old, array $new, array $changedA, array $changedB): array
    {
        $pair = static fn(string $line, bool $isChanged): array => [$line, $isChanged];

        $added = [];
        $gap = 0;

        foreach (array_map($pair, $new, $changedB) as [$line, $isChanged]) {
            if ($isChanged) {
                $added[$gap][] = new DiffLine(DiffOp::Add, $line);
            } else {
                $gap++;
            }
        }

        $script = [];
        $gap = 0;

        foreach (array_map($pair, $old, $changedA) as [$line, $isChanged]) {
            if ($isChanged) {
                $script[] = new DiffLine(DiffOp::Remove, $line);

                continue;
            }

            array_push($script, ...$added[$gap] ?? []);

            $script[] = new DiffLine(DiffOp::Context, $line);

            $gap++;
        }

        return [...$script, ...$added[$gap] ?? []];
    }

    /**
     * Slides every group of changed lines to its canonical position, as Git's `xdl_change_compact()`: in line with a
     * group of changes of the other side when possible, otherwise where the indent heuristic scores best.
     *
     * A group with `k` unchanged lines before it lines up with the changes the other side holds before its own `k`-th
     * unchanged line, since unchanged lines pair up in order.
     *
     * @param list<int> $lines Interned lines of the side being compacted.
     * @param list<int> $indents Indentation of each line of the side being compacted.
     * @param array<int, bool> $changed Change flags of the side being compacted, updated in place.
     * @param array<int, bool> $otherChanged Change flags of the other side.
     */
    private static function compact(array $lines, array $indents, array &$changed, array $otherChanged): void
    {
        $count = count($lines);
        $otherGaps = self::gaps($otherChanged);

        $gap = 0;
        $start = 0;

        $end = self::groupEnd($changed, 0);

        while (true) {
            if ($end !== $start) {
                do {
                    $size = $end - $start;

                    // A line before the first or after the last one is missing and matches no line.
                    while (($lines[$start - 1] ?? null) === ($lines[$end - 1] ?? null)) {
                        $changed[--$start] = true;
                        $changed[--$end] = false;
                        $gap--;

                        while ($changed[$start - 1] ?? false) {
                            $start--;
                        }
                    }

                    $earliestEnd = $end;
                    $matchingEnd = ($otherGaps[$gap] ?? false) ? $end : null;

                    while (($lines[$start] ?? null) === ($lines[$end] ?? null)) {
                        $changed[$start++] = false;
                        $changed[$end++] = true;
                        $gap++;

                        $end = self::groupEnd($changed, $end);

                        if ($otherGaps[$gap] ?? false) {
                            $matchingEnd = $end;
                        }
                    }
                } while ($size !== $end - $start);

                // The group no longer merges: move it back up to the last end lined up with changes of the other side,
                // or else to the end chosen by the indent heuristic.
                $targetEnd = $matchingEnd ?? self::bestEnd($indents, $earliestEnd, $end, $size);

                while ($end > $targetEnd) {
                    $changed[--$start] = true;
                    $changed[--$end] = false;
                    $gap--;
                }
            }

            if ($end === $count) {
                return;
            }

            $start = $end + 1;
            $end = self::groupEnd($changed, $start);
            $gap++;
        }
    }

    /**
     * Compares two indent-heuristic scores; a negative or zero result means `$score` is at least as good as `$best`.
     *
     * @param array{int, int} $score Effective indent and penalty of the candidate.
     * @param array{int, int} $best Effective indent and penalty of the best candidate so far.
     */
    private static function compareScores(array $score, array $best): int
    {
        return IndentHeuristic::INDENT_WEIGHT * ($score[0] <=> $best[0]) + $score[1] - $best[1];
    }

    /**
     * Returns the positions, counted in unchanged lines, that changed lines occupy.
     *
     * @param array<int, bool> $changed Change flags.
     *
     * @return array<int, true> `true` for every number of unchanged lines that some changed line follows.
     */
    private static function gaps(array $changed): array
    {
        $gaps = [];
        $gap = 0;

        foreach ($changed as $isChanged) {
            if ($isChanged) {
                $gaps[$gap] = true;
            } else {
                $gap++;
            }
        }

        return $gaps;
    }

    /**
     * Returns the exclusive end of the run of changed lines that starts at `$from`; the end of the input ends the run.
     *
     * @param array<int, bool> $changed Change flags.
     * @param int $from Start of the run.
     */
    private static function groupEnd(array $changed, int $from): int
    {
        while ($changed[$from] ?? false) {
            $from++;
        }

        return $from;
    }

    /**
     * Returns the indentation of a line as Git's indent heuristic measures it: a tab advances to the next tab stop, the
     * value stops at `IndentHeuristic::MAX_INDENT`, and a line holding only spaces, tabs and carriage returns measures
     * `IndentHeuristic::BLANK`.
     */
    private static function indent(string $line): int
    {
        $indent = 0;
        $length = strlen($line);

        for ($index = 0; $index < $length; $index++) {
            $char = $line[$index];

            if ($char === ' ') {
                $indent++;
            } elseif ($char === "\t") {
                $indent += IndentHeuristic::TAB_WIDTH - $indent % IndentHeuristic::TAB_WIDTH;
            } elseif ($char !== "\r") {
                return $indent;
            }

            if ($indent >= IndentHeuristic::MAX_INDENT) {
                return IndentHeuristic::MAX_INDENT;
            }
        }

        return IndentHeuristic::BLANK;
    }

    /**
     * Returns the indentation of every line.
     *
     * @param list<string> $lines Lines to measure.
     *
     * @return list<int> Indentation per line, `IndentHeuristic::BLANK` for whitespace-only lines.
     */
    private static function indents(array $lines): array
    {
        $indents = [];

        foreach ($lines as $line) {
            $indents[] = self::indent($line);
        }

        return $indents;
    }

    /**
     * Returns whether a multi-matched line sits inside a run of unmatched lines and is discarded before the alignment,
     * as Git's `xdl_clean_mmatch()`.
     *
     * @param array<int, LineMatch> $kinds Classification of the lines between the common prefix and suffix.
     * @param int $index Index of the multi-matched line.
     */
    private static function isDiscardable(array $kinds, int $index): bool
    {
        [$unmatchedBefore, $multiBefore] = self::scanRun($kinds, $index - 1, -1);

        if ($unmatchedBefore === 0) {
            return false;
        }

        [$unmatchedAfter, $multiAfter] = self::scanRun($kinds, $index + 1, 1);

        if ($unmatchedAfter === 0) {
            return false;
        }

        $multi = $multiBefore + $multiAfter;

        return $multi * MultimatchCleanup::KEEP_RUN < $multi + $unmatchedBefore + $unmatchedAfter;
    }

    /**
     * Marks the changed lines of both inputs, as Git's `xdl_do_diff()` with the default settings.
     *
     * @param list<int> $a Interned old lines.
     * @param list<int> $b Interned new lines.
     * @param array<int, bool> $changedA Change flags of the old lines, updated in place.
     * @param array<int, bool> $changedB Change flags of the new lines, updated in place.
     */
    private static function markChanges(array $a, array $b, array &$changedA, array &$changedB): void
    {
        $prefix = 0;

        while (isset($a[$prefix], $b[$prefix]) && $a[$prefix] === $b[$prefix]) {
            $prefix++;
        }

        $endA = count($a);
        $endB = count($b);

        while ($endA > $prefix && $endB > $prefix && ($a[$endA - 1] ?? null) === ($b[$endB - 1] ?? null)) {
            $endA--;
            $endB--;
        }

        [$reducedA, $indexA] = self::reduce($a, $prefix, $endA, array_count_values($b), $changedA);
        [$reducedB, $indexB] = self::reduce($b, $prefix, $endB, array_count_values($a), $changedB);

        $boxes = [[0, count($reducedA), 0, count($reducedB)]];

        while (($box = array_pop($boxes)) !== null) {
            [$off1, $lim1, $off2, $lim2] = $box;

            while ($off1 < $lim1 && $off2 < $lim2 && ($reducedA[$off1] ?? null) === ($reducedB[$off2] ?? null)) {
                $off1++;
                $off2++;
            }

            while (
                $off1 < $lim1
                && $off2 < $lim2
                && ($reducedA[$lim1 - 1] ?? null) === ($reducedB[$lim2 - 1] ?? null)
            ) {
                $lim1--;
                $lim2--;
            }

            if ($off1 === $lim1 || $off2 === $lim2) {
                foreach (array_slice($indexA, $off1, $lim1 - $off1) as $position) {
                    $changedA[$position] = true;
                }

                foreach (array_slice($indexB, $off2, $lim2 - $off2) as $position) {
                    $changedB[$position] = true;
                }

                continue;
            }

            [$split1, $split2] = self::split($reducedA, $reducedB, $off1, $lim1, $off2, $lim2);

            $boxes[] = [$split1, $lim1, $split2, $lim2];
            $boxes[] = [$off1, $split1, $off2, $split2];
        }
    }

    /**
     * Returns the lines of `$lines[$start..$end)` that take part in the alignment and marks the others as changed, as
     * Git's `xdl_cleanup_records()`.
     *
     * @param list<int> $lines Interned lines of one input.
     * @param int $start First index after the common prefix.
     * @param int $end Exclusive index before the common suffix.
     * @param array<int, int> $otherCounts Number of occurrences of each interned line in the other input.
     * @param array<int, bool> $changed Change flags of `$lines`, updated in place.
     *
     * @return array{list<int>, list<int>} Retained interned lines and their indexes in `$lines`.
     */
    private static function reduce(array $lines, int $start, int $end, array $otherCounts, array &$changed): array
    {
        $limit = min(self::bogoSqrt(count($lines)), MultimatchCleanup::MAX_EQUAL_LIMIT);
        $middle = array_slice($lines, $start, $end - $start, true);

        $kinds = [];

        foreach ($middle as $index => $line) {
            $matches = $otherCounts[$line] ?? 0;
            $kinds[$index] = match (true) {
                $matches === 0 => LineMatch::Unmatched,
                $matches < $limit => LineMatch::Matched,
                default => LineMatch::Multimatched,
            };
        }

        $reduced = [];
        $positions = [];

        foreach ($middle as $index => $line) {
            $retained = match ($kinds[$index] ?? null) {
                LineMatch::Matched => true,
                LineMatch::Multimatched => !self::isDiscardable($kinds, $index),
                default => false,
            };

            if ($retained) {
                $reduced[] = $line;
                $positions[] = $index;
            } else {
                $changed[$index] = true;
            }
        }

        return [$reduced, $positions];
    }

    /**
     * Returns the indent-heuristic penalty of a split from the indentation of the lines around it.
     *
     * @param int $indent Effective indentation after the split, `IndentHeuristic::BLANK` when only blank lines follow.
     * @param int $preIndent Indentation of the previous non-blank line, `IndentHeuristic::BLANK` when there is none.
     * @param int $postIndent Indentation of the next non-blank line after the line that follows the split.
     * @param bool $anyBlanks Whether blank lines surround the split.
     */
    private static function relativeIndentPenalty(int $indent, int $preIndent, int $postIndent, bool $anyBlanks): int
    {
        if ($indent === IndentHeuristic::BLANK || $preIndent === IndentHeuristic::BLANK) {
            return 0;
        }

        if ($indent < $preIndent) {
            if ($postIndent > $indent) {
                return $anyBlanks
                    ? IndentHeuristic::RELATIVE_OUTDENT_WITH_BLANK_PENALTY
                    : IndentHeuristic::RELATIVE_OUTDENT_PENALTY;
            }

            return $anyBlanks
                ? IndentHeuristic::RELATIVE_DEDENT_WITH_BLANK_PENALTY
                : IndentHeuristic::RELATIVE_DEDENT_PENALTY;
        }

        if ($indent > $preIndent) {
            return $anyBlanks
                ? IndentHeuristic::RELATIVE_INDENT_WITH_BLANK_PENALTY
                : IndentHeuristic::RELATIVE_INDENT_PENALTY;
        }

        return 0;
    }

    /**
     * Counts the blank lines from `$from` in direction `$step` and returns the indentation of the first non-blank line.
     *
     * @param list<int> $indents Indentation of each line, `IndentHeuristic::BLANK` for whitespace-only lines.
     * @param int $from Index of the first line examined.
     * @param int $step Direction of the walk, `-1` towards the start and `1` towards the end.
     *
     * @return array{int, int} Number of blank lines and indentation of the next non-blank line, which is `0` once
     * `IndentHeuristic::MAX_BLANKS` blank lines are counted and `IndentHeuristic::BLANK` when the input ends first.
     */
    private static function scanBlanks(array $indents, int $from, int $step): array
    {
        $blanks = 0;

        for ($index = $from; isset($indents[$index]); $index += $step) {
            if ($indents[$index] !== IndentHeuristic::BLANK) {
                return [$blanks, $indents[$index]];
            }

            $blanks++;

            if ($blanks === IndentHeuristic::MAX_BLANKS) {
                return [$blanks, 0];
            }
        }

        return [$blanks, IndentHeuristic::BLANK];
    }

    /**
     * Counts the run of unmatched and multi-matched lines that starts at `$from` in direction `$step`, over at most
     * `MultimatchCleanup::SCAN_WINDOW` lines.
     *
     * @param array<int, LineMatch> $kinds Classification of the lines between the common prefix and suffix.
     * @param int $from Index next to the multi-matched line the run surrounds.
     * @param int $step Direction of the walk, `-1` towards the start and `1` towards the end.
     *
     * @return array{int, int} Number of unmatched lines and of multi-matched lines, the latter counting the line the
     * run surrounds too.
     */
    private static function scanRun(array $kinds, int $from, int $step): array
    {
        $unmatched = 0;
        $multi = 1;

        for ($offset = 0; $offset < MultimatchCleanup::SCAN_WINDOW; $offset++) {
            // A line outside the examined range ends the run as a matched line does.
            $kind = $kinds[$from + $step * $offset] ?? LineMatch::Matched;

            if ($kind === LineMatch::Unmatched) {
                $unmatched++;
            } elseif ($kind === LineMatch::Multimatched) {
                $multi++;
            } else {
                break;
            }
        }

        return [$unmatched, $multi];
    }

    /**
     * Scores the split before line `$split` as Git's indent heuristic does.
     *
     * @param list<int> $indents Indentation of each line, `IndentHeuristic::BLANK` for whitespace-only lines.
     * @param int $split Index of the line that follows the split; the line count for the end of the input.
     *
     * @return array{int, int} Effective indent and penalty of the split.
     */
    private static function scoreSplit(array $indents, int $split): array
    {
        $indent = $indents[$split] ?? IndentHeuristic::BLANK;

        [$preBlank, $preIndent] = self::scanBlanks($indents, $split - 1, -1);
        [$postBlank, $postIndent] = self::scanBlanks($indents, $split + 1, 1);

        $blankAfter = $indent === IndentHeuristic::BLANK ? 1 + $postBlank : 0;

        $totalBlank = $preBlank + $blankAfter;

        $effectiveIndent = $indent === IndentHeuristic::BLANK ? $postIndent : $indent;

        $penalty = ($split === 0 ? IndentHeuristic::START_OF_FILE_PENALTY : 0)
            + (isset($indents[$split]) ? 0 : IndentHeuristic::END_OF_FILE_PENALTY)
            + IndentHeuristic::TOTAL_BLANK_WEIGHT * $totalBlank
            + IndentHeuristic::POST_BLANK_WEIGHT * $blankAfter
            + self::relativeIndentPenalty($effectiveIndent, $preIndent, $postIndent, $totalBlank !== 0);

        return [$effectiveIndent, $penalty];
    }

    /**
     * Finds the middle snake of the box `[$off1, $lim1) x [$off2, $lim2)` and returns the point that splits it into two
     * independent boxes, as Git's `xdl_split()` without its cost-limiting heuristics.
     *
     * The furthest-reaching old index of each diagonal is kept for the forward and the backward search; a diagonal
     * without an entry lies outside the search window and never wins the comparison with its neighbor. Unlike Git, the
     * window is not clamped to the box: a path that leaves the box never meets a snake again, so the clamp only saves
     * work and never changes the split.
     *
     * @param list<int> $a Retained interned old lines.
     * @param list<int> $b Retained interned new lines.
     * @param int $off1 Inclusive start of the old range.
     * @param int $lim1 Exclusive end of the old range.
     * @param int $off2 Inclusive start of the new range.
     * @param int $lim2 Exclusive end of the new range.
     *
     * @return array{int, int} Split point as an old and a new index.
     */
    private static function split(array $a, array $b, int $off1, int $lim1, int $off2, int $lim2): array
    {
        $forwardMid = $off1 - $off2;
        $backwardMid = $lim1 - $lim2;
        $odd = ($forwardMid & 1) !== ($backwardMid & 1);
        $forward = [$forwardMid => $off1];
        $backward = [$backwardMid => $lim1];

        for ($cost = 1; ; $cost++) {
            for ($d = $forwardMid + $cost; $d >= $forwardMid - $cost; $d -= 2) {
                $previous = $forward[$d - 1] ?? PHP_INT_MIN;
                $next = $forward[$d + 1] ?? PHP_INT_MIN;
                $i1 = $previous >= $next ? $previous + 1 : $next;
                $i2 = $i1 - $d;

                while (
                    $i1 < $lim1
                    && $i2 < $lim2
                    && ($a[$i1] ?? null) === ($b[$i2] ?? null)
                ) {
                    $i1++;
                    $i2++;
                }

                $forward[$d] = $i1;

                if ($odd && ($backward[$d] ?? PHP_INT_MAX) <= $i1) {
                    return [$i1, $i2];
                }
            }

            for ($d = $backwardMid + $cost; $d >= $backwardMid - $cost; $d -= 2) {
                $previous = $backward[$d - 1] ?? PHP_INT_MAX;
                $next = $backward[$d + 1] ?? PHP_INT_MAX;
                $i1 = $previous < $next ? $previous : $next - 1;
                $i2 = $i1 - $d;

                while (
                    $i1 > $off1
                    && $i2 > $off2
                    && ($a[$i1 - 1] ?? null) === ($b[$i2 - 1] ?? null)
                ) {
                    $i1--;
                    $i2--;
                }

                $backward[$d] = $i1;

                if (!$odd && $i1 <= ($forward[$d] ?? PHP_INT_MIN)) {
                    return [$i1, $i2];
                }
            }
        }
    }
}
