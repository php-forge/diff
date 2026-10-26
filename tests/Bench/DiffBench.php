<?php

declare(strict_types=1);

namespace PHPForge\Diff\Tests\Bench;

use PHPForge\Diff\{LineDiffer, UnifiedDiffRenderer};
use Testo\Bench;

use function implode;
use function max;

use const INF;

/**
 * Benchmark measuring {@see LineDiffer} on its own and behind {@see UnifiedDiffRenderer} on the same input.
 *
 * The marked method is the bare differ (alias `current`); the renderer adds the line-ending normalization, the hunk
 * grouping and the formatting on top of the same diff. Every callable builds the same input pair per call, so the
 * shared `O(N)` setup cancels out and the gap reflects the rendering cost. The tolerance is infinite: the benchmark
 * measures and ranks without gating on the result.
 */
final class DiffBench
{
    /**
     * Diffs the generated input pair with {@see LineDiffer}.
     *
     * @param int $lines Number of lines on each side.
     * @param int $changes Number of lines altered on the new side.
     */
    #[Bench(
        callables: [
            'unified' => [self::class, 'unified'],
        ],
        arguments: [2000, 40],
        warmup: 2,
        calls: 5,
        iterations: 5,
        tolerance: INF,
    )]
    public static function lineDiffer(int $lines, int $changes): void
    {
        [$old, $new] = self::pair($lines, $changes);

        (new LineDiffer())->diff($old, $new);
    }

    /**
     * Renders the generated input pair with {@see UnifiedDiffRenderer}.
     *
     * @param int $lines Number of lines on each side.
     * @param int $changes Number of lines altered on the new side.
     */
    public static function unified(int $lines, int $changes): void
    {
        [$old, $new] = self::pair($lines, $changes);

        (new UnifiedDiffRenderer())->render($old, $new, 'a', 'b');
    }

    /**
     * Builds two nearly identical multi-line strings with `$changes` of `$lines` lines altered and evenly spread.
     *
     * @param int $lines Number of lines on each side.
     * @param int $changes Number of lines altered on the new side.
     *
     * @return array{string, string} Old and new multi-line strings.
     */
    private static function pair(int $lines, int $changes): array
    {
        $old = [];
        $new = [];

        for ($i = 0; $i < $lines; $i++) {
            $old[$i] = $new[$i] = "line {$i} content here";
        }

        for ($c = 0; $c < $changes && $c < $lines; $c++) {
            $index = (int) ($c * ($lines / max(1, $changes)));
            $new[$index] = "CHANGED {$index}";
        }

        return [implode("\n", $old), implode("\n", $new)];
    }
}
