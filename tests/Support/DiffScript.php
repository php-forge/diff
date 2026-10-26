<?php

declare(strict_types=1);

namespace PHPForge\Diff\Tests\Support;

use PHPForge\Diff\DiffLine;
use PHPForge\Diff\DiffOp;

use function array_map;
use function implode;

/**
 * Converts edit scripts returned by {@see \PHPForge\Diff\DifferInterface} implementations into comparable values.
 *
 * Shared by the unit tests to reconstruct either side of a diff, render it as readable snapshot lines, encode its
 * operations and count its edits.
 */
final class DiffScript
{
    /**
     * Counts the lines of a diff that are not {@see DiffOp::Context}.
     *
     * @param list<DiffLine> $diff Edit script to inspect.
     *
     * @return int<0, max> Number of removed and added lines.
     */
    public static function editCount(array $diff): int
    {
        $count = 0;

        foreach ($diff as $line) {
            if ($line->op !== DiffOp::Context) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Encodes the operations of a diff as one character per line: `=` kept, `-` removed and `+` added.
     *
     * @param list<DiffLine> $diff Edit script to encode.
     *
     * @return string Operations in script order.
     */
    public static function operations(array $diff): string
    {
        return implode(
            '',
            array_map(
                static fn(DiffLine $line): string => match ($line->op) {
                    DiffOp::Context => '=',
                    DiffOp::Remove => '-',
                    DiffOp::Add => '+',
                },
                $diff,
            ),
        );
    }

    /**
     * Reconstructs one side of the comparison by dropping the edits that belong to the other side.
     *
     * Excluding {@see DiffOp::Add} leaves the old side; excluding {@see DiffOp::Remove} leaves the new side.
     *
     * @param list<DiffLine> $diff Edit script to reconstruct from.
     * @param DiffOp $exclude Operation whose lines are dropped.
     *
     * @return string Lines of the reconstructed side joined with `\n`.
     */
    public static function reconstruct(array $diff, DiffOp $exclude): string
    {
        $lines = [];

        foreach ($diff as $line) {
            if ($line->op !== $exclude) {
                $lines[] = $line->line;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Renders a diff as one prefixed string per line for exact, readable alignment snapshots.
     *
     * Context lines are prefixed with two spaces, removed lines with `- ` and added lines with `+ `.
     *
     * @param list<DiffLine> $diff Edit script to render.
     *
     * @return list<string> Rendered lines in script order.
     */
    public static function render(array $diff): array
    {
        return array_map(
            static fn(DiffLine $line): string => match ($line->op) {
                DiffOp::Context => "  {$line->line}",
                DiffOp::Remove => "- {$line->line}",
                DiffOp::Add => "+ {$line->line}",
            },
            $diff,
        );
    }
}
