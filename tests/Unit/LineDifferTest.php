<?php

declare(strict_types=1);

namespace PHPForge\Diff\Tests\Unit;

use ErrorException;
use PHPForge\Diff\{DiffLine, DiffOp, IndentHeuristic, LineDiffer, LineMatch, MultimatchCleanup};
use PHPForge\Diff\Tests\Provider\LineDifferProvider;
use PHPForge\Diff\Tests\Support\DiffScript;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Lifecycle\{AfterTest, BeforeTest};
use Testo\Test;

use function array_map;
use function array_reverse;
use function implode;
use function range;
use function restore_error_handler;
use function set_error_handler;
use function str_replace;

/**
 * Unit tests for {@see LineDiffer} checking edit scripts against `git diff`, the reconstruction invariant, the scripts
 * of large inputs and the tuning values shared with Git.
 *
 * Every test runs with PHP warnings promoted to exceptions, so an out-of-range read fails the test instead of passing
 * silently.
 *
 * {@see LineDifferProvider} for test case data providers.
 */
#[Test]
#[Covers(LineDiffer::class)]
#[Covers(DiffLine::class)]
#[Covers(IndentHeuristic::class)]
#[Covers(LineMatch::class)]
#[Covers(MultimatchCleanup::class)]
final class LineDifferTest
{
    /**
     * Ensures a 2000-line input with a change every ten lines edits only the changed lines and rebuilds the new side.
     */
    public function editsOnlyTheChangedLinesOfTwoThousandLines(): void
    {
        $old = implode(
            "\n",
            array_map(static fn(int $index): string => "line {$index}", range(1, 2000)),
        );
        $new = implode(
            "\n",
            array_map(
                static fn(int $index): string => $index % 10 === 1 ? "changed {$index}" : "line {$index}",
                range(1, 2000),
            ),
        );

        $diff = (new LineDiffer())->diff($old, $new);

        Assert::same(
            DiffScript::editCount($diff),
            400,
            'Only the changed lines should be edits.',
        );
        Assert::same(
            DiffScript::reconstruct($diff, DiffOp::Remove),
            $new,
            'New side should be rebuilt.',
        );
    }

    /**
     * Pins the tuning values of the indent heuristic to those of Git's `xdiffi.c`.
     */
    public function indentHeuristicUsesGitValues(): void
    {
        Assert::same(
            [
                'BLANK' => IndentHeuristic::BLANK,
                'END_OF_FILE_PENALTY' => IndentHeuristic::END_OF_FILE_PENALTY,
                'INDENT_WEIGHT' => IndentHeuristic::INDENT_WEIGHT,
                'MAX_BLANKS' => IndentHeuristic::MAX_BLANKS,
                'MAX_INDENT' => IndentHeuristic::MAX_INDENT,
                'MAX_SLIDING' => IndentHeuristic::MAX_SLIDING,
                'POST_BLANK_WEIGHT' => IndentHeuristic::POST_BLANK_WEIGHT,
                'RELATIVE_DEDENT_PENALTY' => IndentHeuristic::RELATIVE_DEDENT_PENALTY,
                'RELATIVE_DEDENT_WITH_BLANK_PENALTY' => IndentHeuristic::RELATIVE_DEDENT_WITH_BLANK_PENALTY,
                'RELATIVE_INDENT_PENALTY' => IndentHeuristic::RELATIVE_INDENT_PENALTY,
                'RELATIVE_INDENT_WITH_BLANK_PENALTY' => IndentHeuristic::RELATIVE_INDENT_WITH_BLANK_PENALTY,
                'RELATIVE_OUTDENT_PENALTY' => IndentHeuristic::RELATIVE_OUTDENT_PENALTY,
                'RELATIVE_OUTDENT_WITH_BLANK_PENALTY' => IndentHeuristic::RELATIVE_OUTDENT_WITH_BLANK_PENALTY,
                'START_OF_FILE_PENALTY' => IndentHeuristic::START_OF_FILE_PENALTY,
                'TAB_WIDTH' => IndentHeuristic::TAB_WIDTH,
                'TOTAL_BLANK_WEIGHT' => IndentHeuristic::TOTAL_BLANK_WEIGHT,
            ],
            [
                'BLANK' => -1,
                'END_OF_FILE_PENALTY' => 21,
                'INDENT_WEIGHT' => 60,
                'MAX_BLANKS' => 20,
                'MAX_INDENT' => 200,
                'MAX_SLIDING' => 100,
                'POST_BLANK_WEIGHT' => 6,
                'RELATIVE_DEDENT_PENALTY' => 23,
                'RELATIVE_DEDENT_WITH_BLANK_PENALTY' => 17,
                'RELATIVE_INDENT_PENALTY' => -4,
                'RELATIVE_INDENT_WITH_BLANK_PENALTY' => 10,
                'RELATIVE_OUTDENT_PENALTY' => 24,
                'RELATIVE_OUTDENT_WITH_BLANK_PENALTY' => 17,
                'START_OF_FILE_PENALTY' => 1,
                'TAB_WIDTH' => 8,
                'TOTAL_BLANK_WEIGHT' => -30,
            ],
            'Values should match Git.',
        );
    }

    /**
     * Verifies the edit operations of the stored `git diff` cases line for line.
     */
    #[DataProvider(LineDifferProvider::class . '::gitCases')]
    public function matchesGitEditOperations(string $old, string $new, string $expected): void
    {
        Assert::same(
            DiffScript::operations((new LineDiffer())->diff($old, $new)),
            $expected,
            'Operations should match `git diff`.',
        );
    }

    /**
     * Pins the limits of the multi-match cleanup to those of Git's `xprepare.c`.
     */
    public function multimatchCleanupUsesGitValues(): void
    {
        Assert::same(
            [
                'KEEP_RUN' => MultimatchCleanup::KEEP_RUN,
                'MAX_EQUAL_LIMIT' => MultimatchCleanup::MAX_EQUAL_LIMIT,
                'SCAN_WINDOW' => MultimatchCleanup::SCAN_WINDOW,
            ],
            [
                'KEEP_RUN' => 4,
                'MAX_EQUAL_LIMIT' => 1024,
                'SCAN_WINDOW' => 100,
            ],
            'Values should match Git.',
        );
    }

    /**
     * Verifies exact scripts derived from `git diff`, including the removals emitted before the additions of a change.
     *
     * @param list<string> $expected
     */
    #[DataProvider(LineDifferProvider::class . '::scripts')]
    public function producesExactScript(string $old, string $new, array $expected): void
    {
        Assert::same(
            DiffScript::render((new LineDiffer())->diff($old, $new)),
            $expected,
            'Rendered script should match `git diff`.',
        );
    }

    /**
     * Promotes every PHP warning and notice raised during a test to an exception, so an out-of-range read fails.
     */
    #[BeforeTest]
    public function promoteWarningsToExceptions(): void
    {
        set_error_handler(
            static fn(int $severity, string $message): never => throw new ErrorException($message, 0, $severity),
        );
    }

    /**
     * Ensures that keeping only the lines present on one side reconstructs that side verbatim.
     */
    #[DataProvider(LineDifferProvider::class . '::scenarios')]
    public function reconstructsBothSides(string $old, string $new): void
    {
        $diff = (new LineDiffer())->diff($old, $new);

        Assert::same(
            DiffScript::reconstruct($diff, DiffOp::Add),
            $old,
            'Old side should be rebuilt.',
        );
        Assert::same(
            DiffScript::reconstruct($diff, DiffOp::Remove),
            $new,
            'New side should be rebuilt.',
        );
    }

    /**
     * Restores the error handler replaced before the test.
     */
    #[AfterTest]
    public function restoreErrorHandler(): void
    {
        restore_error_handler();
    }

    /**
     * Ensures a reversed input keeps exactly one common line.
     */
    public function reversalKeepsOneLine(): void
    {
        $lines = array_map(static fn(int $index): string => "line {$index}", range(1, 50));
        $old = implode(
            "\n",
            $lines,
        );
        $new = implode(
            "\n",
            array_reverse($lines),
        );

        $diff = (new LineDiffer())->diff($old, $new);

        Assert::same(
            DiffScript::editCount($diff),
            98,
            'Every line but one should be an edit.',
        );
        Assert::same(
            DiffScript::reconstruct($diff, DiffOp::Add),
            $old,
            'Old side should be rebuilt.',
        );
        Assert::same(
            DiffScript::reconstruct($diff, DiffOp::Remove),
            $new,
            'New side should be rebuilt.',
        );
    }

    /**
     * Ensures a large, nearly identical input yields a single remove/add pair.
     */
    public function scalesToLargeNearlyIdenticalInput(): void
    {
        $old = implode(
            "\n",
            array_map(static fn(int $i): string => "line {$i}", range(1, 5000)),
        );
        $new = str_replace('line 2500', 'line CHANGED', $old);

        $diff = (new LineDiffer())->diff($old, $new);

        Assert::same(
            DiffScript::editCount($diff),
            2,
            'Script should hold one remove/add pair.',
        );
        Assert::same(
            DiffScript::reconstruct($diff, DiffOp::Add),
            $old,
            'Old side should be rebuilt.',
        );
        Assert::same(
            DiffScript::reconstruct($diff, DiffOp::Remove),
            $new,
            'New side should be rebuilt.',
        );
    }
}
