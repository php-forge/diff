<?php

declare(strict_types=1);

namespace PHPForge\Diff;

/**
 * Computes a line-by-line edit script between two multi-line strings.
 *
 * Implementations split both inputs on `"\n"` with {@see explode()}, so an empty string is one empty line and a
 * trailing newline yields a trailing empty line. Every input line appears exactly once in the returned script, tagged
 * as kept ({@see DiffOp::Context}), removed from the old input ({@see DiffOp::Remove}) or added from the new input
 * ({@see DiffOp::Add}).
 */
interface DifferInterface
{
    /**
     * Returns the edit script that transforms `$old` into `$new`.
     *
     * @param string $old Original input, split into lines on `"\n"`.
     * @param string $new Target input, split into lines on `"\n"`.
     *
     * @return list<DiffLine> Edit script; its `Context` and `Remove` lines in order reproduce the lines of `$old`, and
     * its `Context` and `Add` lines in order reproduce the lines of `$new`.
     */
    public function diff(string $old, string $new): array;
}
