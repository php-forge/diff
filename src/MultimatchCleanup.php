<?php

declare(strict_types=1);

namespace PHPForge\Diff;

/**
 * Holds the limits of Git's cleanup of multi-matched lines, which {@see LineDiffer} applies before the alignment so
 * that lines repeated all over the other input do not anchor it.
 *
 * The values match those of Git's `xprepare.c`.
 *
 * @see https://github.com/git/git/blob/master/xdiff/xprepare.c Git's `xprepare.c`.
 */
enum MultimatchCleanup
{
    /**
     * Multi-matched lines are discarded when they make up less than `1 / KEEP_RUN` of the run of unmatched and
     * multi-matched lines around them.
     */
    public const int KEEP_RUN = 4;

    /**
     * Upper bound of the occurrence count from which a line counts as multi-matched; below it the bound is the
     * approximate square root of the line count.
     */
    public const int MAX_EQUAL_LIMIT = 1024;

    /**
     * Number of lines examined on each side of a multi-matched line when deciding whether to discard it.
     */
    public const int SCAN_WINDOW = 100;
}
