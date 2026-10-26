<?php

declare(strict_types=1);

namespace PHPForge\Diff\Tests\Stub;

use Override;
use PHPForge\Diff\{DiffLine, DifferInterface};

/**
 * Spy {@see DifferInterface} that records every call and delegates the work to a wrapped differ.
 *
 * Used to assert how {@see \PHPForge\Diff\UnifiedDiffRenderer} drives its differ: which inputs it hands over and when
 * it skips the differ.
 */
final class RecordingDiffer implements DifferInterface
{
    /**
     * Arguments of every recorded call, in call order.
     *
     * @var list<array{string, string}>
     */
    public array $calls = [];

    /**
     * Creates a spy around `$delegate`.
     *
     * @param DifferInterface $delegate Differ that computes the returned scripts.
     */
    public function __construct(private readonly DifferInterface $delegate) {}

    /**
     * Records the call and returns the script of the wrapped differ.
     *
     * @return list<DiffLine>
     */
    #[Override]
    public function diff(string $old, string $new): array
    {
        $this->calls[] = [$old, $new];

        return $this->delegate->diff($old, $new);
    }
}
