<?php

declare(strict_types=1);

namespace PHPForge\Diff;

/**
 * Represents a single line of an edit script produced by {@see DifferInterface}.
 */
final readonly class DiffLine
{
    /**
     * Creates a diff line.
     *
     * @param DiffOp $op Operation applied to the line.
     * @param string $line Line content without its trailing `"\n"`; a `"\r"` left by CRLF input is kept.
     */
    public function __construct(
        public DiffOp $op,
        public string $line,
    ) {}
}
