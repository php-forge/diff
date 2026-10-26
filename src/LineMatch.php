<?php

declare(strict_types=1);

namespace PHPForge\Diff;

/**
 * Represents how often the content of a line occurs in the other input, as Git's `xdl_cleanup_records()` classifies the
 * lines before the alignment of {@see LineDiffer}.
 */
enum LineMatch
{
    /**
     * The line occurs in the other input fewer times than the multi-match limit and takes part in the alignment.
     */
    case Matched;

    /**
     * The line occurs in the other input at least as many times as the multi-match limit and is discarded when it sits
     * inside a run of unmatched lines.
     */
    case Multimatched;

    /**
     * The line does not occur in the other input and is always changed.
     */
    case Unmatched;
}
