<?php

declare(strict_types=1);

namespace PHPForge\Diff\Tests\Unit;

use ErrorException;
use LogicException;
use PHPForge\Diff\{DiffLine, DiffOp, LineDiffer, UnifiedDiffRenderer};
use PHPForge\Diff\Tests\Provider\UnifiedDiffRendererProvider;
use PHPForge\Diff\Tests\Stub\{FixedScriptDiffer, RecordingDiffer};
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Lifecycle\{AfterTest, BeforeTest};
use Testo\Test;

use function restore_error_handler;
use function set_error_handler;
use function str_ends_with;
use function str_starts_with;

use const PHP_INT_MAX;

/**
 * Unit tests for {@see UnifiedDiffRenderer} checking byte-exact compatibility with `git diff` output.
 *
 * Every test runs with PHP warnings promoted to exceptions, so an out-of-range read fails the test instead of passing
 * silently.
 *
 * {@see UnifiedDiffRendererProvider} for test case data providers.
 */
#[Test]
#[Covers(UnifiedDiffRenderer::class)]
final class UnifiedDiffRendererTest
{
    /**
     * Ensures the differ receives the lines without terminators and a carriage return for a missing final newline.
     */
    public function handsLinesWithoutTerminatorsToTheDiffer(): void
    {
        $spy = new RecordingDiffer(new LineDiffer());

        (new UnifiedDiffRenderer($spy))->render("a\nb\n", "a\nb", 'a', 'b');

        Assert::same(
            $spy->calls,
            [["a\nb", "a\nb\r"]],
            'Differ input should mark the missing newline.',
        );
    }

    /**
     * Ensures texts that differ only in their line endings render nothing.
     */
    public function ignoresLineEndingOnlyDifferences(): void
    {
        Assert::same(
            (new UnifiedDiffRenderer())->render("a\r\nb\rc\n", "a\nb\nc\n", 'a/f', 'b/f'),
            '',
            'CRLF and CR terminators should be normalized to LF.',
        );
    }

    /**
     * Verifies the output of `git diff --unified=3` byte for byte.
     */
    #[DataProvider(UnifiedDiffRendererProvider::class . '::gitCases')]
    public function matchesGitOutput(string $old, string $new, string $expected): void
    {
        Assert::same(
            (new UnifiedDiffRenderer())->render($old, $new, 'a/a', 'b/b'),
            $expected,
            'Output should match `git diff` byte for byte.',
        );
    }

    /**
     * Verifies the output of `git diff --unified=<n>` byte for byte.
     */
    #[DataProvider(UnifiedDiffRendererProvider::class . '::contextCases')]
    public function matchesGitOutputForCustomContext(string $old, string $new, int $context, string $expected): void
    {
        Assert::same(
            (new UnifiedDiffRenderer())->render($old, $new, 'a/a', 'b/b', $context),
            $expected,
            'Output should match `git diff` byte for byte.',
        );
    }

    /**
     * Ensures carriage returns inside changed lines are normalized away.
     */
    public function normalizesCrlfInsideChangedLines(): void
    {
        Assert::same(
            (new UnifiedDiffRenderer())->render("a\r\nb\r\n", "a\nc\n", 'old', 'new'),
            "--- old\n+++ new\n@@ -1,2 +1,2 @@\n a\n-b\n+c",
            'Changed lines should be rendered without carriage returns.',
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
     * Ensures the lines are rendered from the texts by position, whatever script the injected differ chooses.
     */
    public function rendersTheScriptOfAnyDiffer(): void
    {
        $differ = new FixedScriptDiffer(
            [
                new DiffLine(DiffOp::Remove, 'ignored'),
                new DiffLine(DiffOp::Add, 'ignored'),
                new DiffLine(DiffOp::Context, 'ignored'),
                new DiffLine(DiffOp::Remove, 'ignored'),
            ],
        );

        Assert::same(
            (new UnifiedDiffRenderer($differ))->render("x\ny\nz", "w\ny\n", 'a', 'b'),
            "--- a\n+++ b\n@@ -1,3 +1,2 @@\n-x\n+w\n y\n-z\n\\ No newline at end of file",
            'Lines should come from the texts.',
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
     * Ensures an empty text is rendered without asking the differ, which cannot express a text without lines.
     */
    public function skipsTheDifferForAnEmptyText(): void
    {
        $spy = new RecordingDiffer(new LineDiffer());
        $renderer = new UnifiedDiffRenderer($spy);

        Assert::same(
            $renderer->render('', "a\n", 'a', 'b'),
            "--- a\n+++ b\n@@ -0,0 +1 @@\n+a",
            'Every new line should be added.',
        );
        Assert::same(
            $renderer->render("a\n", '', 'a', 'b'),
            "--- a\n+++ b\n@@ -1 +0,0 @@\n-a",
            'Every old line should be removed.',
        );
        Assert::same(
            $spy->calls,
            [],
            'Differ should not be called.',
        );
    }

    /**
     * Ensures a script that does not reproduce the texts is rejected instead of rendered.
     *
     * @param list<DiffLine> $script
     */
    #[DataProvider(UnifiedDiffRendererProvider::class . '::mismatchedScripts')]
    public function throwLogicExceptionWhenTheScriptDoesNotReproduceTheTexts(
        string $old,
        string $new,
        array $script,
    ): void {
        Expect::exception(LogicException::class)
            ->withMessage('The differ returned a script that does not reproduce its inputs.');

        (new UnifiedDiffRenderer(new FixedScriptDiffer($script)))->render($old, $new, 'a', 'b');
    }

    /**
     * Ensures a context longer than both texts renders every line in one hunk without overflowing the hunk bounds.
     */
    public function treatsMaximumContextAsFullContext(): void
    {
        $old = "a\nb\nc\nd\ne\nf\ng\n";
        $new = "a\nB\nc\nd\ne\nF\ng\n";
        $expected = "--- a\n+++ b\n@@ -1,7 +1,7 @@\n a\n-b\n+B\n c\n d\n e\n-f\n+F\n g";

        $renderer = new UnifiedDiffRenderer();

        Assert::same(
            $renderer->render($old, $new, 'a', 'b', PHP_INT_MAX),
            $expected,
            'Maximum context should render every line.',
        );
        Assert::same(
            $renderer->render($old, $new, 'a', 'b', 7),
            $expected,
            'Maximum context should match a context as long as the texts.',
        );
    }

    /**
     * Ensures a negative context behaves as `0`.
     */
    public function treatsNegativeContextAsZero(): void
    {
        Assert::same(
            (new UnifiedDiffRenderer())->render("x\ny\nw\n", "x\nz\nw\n", 'a', 'b', -5),
            "--- a\n+++ b\n@@ -2 +2 @@ x\n-y\n+z",
            'Negative context should behave as `0`.',
        );
    }

    /**
     * Ensures the labels are written without prefixes or timestamps and the output has no trailing newline.
     */
    public function writesLabelsVerbatim(): void
    {
        $diff = (new UnifiedDiffRenderer())->render("a\n", "b\n", 'current/.editorconfig', 'provider/.editorconfig');

        Assert::true(
            str_starts_with($diff, "--- current/.editorconfig\n+++ provider/.editorconfig\n@@ -1 +1 @@\n"),
            'Labels should be written verbatim.',
        );
        Assert::false(
            str_ends_with($diff, "\n"),
            'Output should not end with a newline.',
        );
    }
}
