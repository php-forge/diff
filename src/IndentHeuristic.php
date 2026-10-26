<?php

declare(strict_types=1);

namespace PHPForge\Diff;

/**
 * Holds the tuning values of Git's indent heuristic, which {@see LineDiffer} uses to choose where a slidable group of
 * changed lines is placed.
 *
 * Each candidate position is scored by the two splits around the group; the values match those of Git's `xdiffi.c`.
 *
 * @see https://github.com/git/git/blob/master/xdiff/xdiffi.c Git's `xdiffi.c`.
 */
enum IndentHeuristic
{
    /**
     * Indentation measured for a line that holds only whitespace.
     */
    public const int BLANK = -1;

    /**
     * Penalty for a split at the end of the input.
     */
    public const int END_OF_FILE_PENALTY = 21;

    /**
     * Weight of the indent comparison against the penalty difference when two positions are compared.
     */
    public const int INDENT_WEIGHT = 60;

    /**
     * Maximum number of consecutive blank lines counted on each side of a split.
     */
    public const int MAX_BLANKS = 20;

    /**
     * Maximum indentation, in columns, that is measured.
     */
    public const int MAX_INDENT = 200;

    /**
     * Maximum number of positions examined for one group.
     */
    public const int MAX_SLIDING = 100;

    /**
     * Weight of the blank lines that follow a split.
     */
    public const int POST_BLANK_WEIGHT = 6;

    /**
     * Penalty for a split before a line indented less than the previous non-blank line and not followed by a more
     * indented line, without blank lines around the split.
     */
    public const int RELATIVE_DEDENT_PENALTY = 23;

    /**
     * Penalty for a split before a line indented less than the previous non-blank line and not followed by a more
     * indented line, with blank lines around the split.
     */
    public const int RELATIVE_DEDENT_WITH_BLANK_PENALTY = 17;

    /**
     * Penalty for a split before a line indented more than the previous non-blank line, without blank lines around the
     * split; negative values favor the split.
     */
    public const int RELATIVE_INDENT_PENALTY = -4;

    /**
     * Penalty for a split before a line indented more than the previous non-blank line, with blank lines around the
     * split.
     */
    public const int RELATIVE_INDENT_WITH_BLANK_PENALTY = 10;

    /**
     * Penalty for a split before a line indented less than the previous non-blank line and followed by a more indented
     * line, without blank lines around the split.
     */
    public const int RELATIVE_OUTDENT_PENALTY = 24;

    /**
     * Penalty for a split before a line indented less than the previous non-blank line and followed by a more indented
     * line, with blank lines around the split.
     */
    public const int RELATIVE_OUTDENT_WITH_BLANK_PENALTY = 17;

    /**
     * Penalty for a split at the start of the input.
     */
    public const int START_OF_FILE_PENALTY = 1;

    /**
     * Number of columns between two tab stops.
     */
    public const int TAB_WIDTH = 8;

    /**
     * Weight of the blank lines around a split; negative values favor blanks.
     */
    public const int TOTAL_BLANK_WEIGHT = -30;
}
