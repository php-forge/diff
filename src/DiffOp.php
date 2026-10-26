<?php

declare(strict_types=1);

namespace PHPForge\Diff;

/**
 * Represents the operation applied to a single line of an edit script produced by {@see DifferInterface}.
 */
enum DiffOp
{
    /**
     * The line is present only in the new input and is added.
     */
    case Add;

    /**
     * The line is present in both inputs and is kept.
     */
    case Context;

    /**
     * The line is present only in the old input and is removed.
     */
    case Remove;
}
