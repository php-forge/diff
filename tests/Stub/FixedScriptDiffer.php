<?php

declare(strict_types=1);

namespace PHPForge\Diff\Tests\Stub;

use Override;
use PHPForge\Diff\{DiffLine, DifferInterface};

/**
 * Stub {@see DifferInterface} that ignores its inputs and returns a fixed edit script.
 *
 * Used to drive {@see \PHPForge\Diff\UnifiedDiffRenderer} with scripts another differ could return, including one that
 * does not reproduce its inputs.
 */
final readonly class FixedScriptDiffer implements DifferInterface
{
    /**
     * Creates a stub that returns `$script`.
     *
     * @param list<DiffLine> $script Edit script returned by every call.
     */
    public function __construct(private array $script) {}

    /**
     * Returns the fixed edit script.
     *
     * @return list<DiffLine>
     */
    #[Override]
    public function diff(string $old, string $new): array
    {
        return $this->script;
    }
}
