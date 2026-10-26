<?php

declare(strict_types=1);

namespace PHPForge\Diff\Tests\Provider;

use PHPForge\Diff\{DiffLine, DiffOp};
use PHPForge\Diff\Tests\Unit\UnifiedDiffRendererTest;

/**
 * Data provider for {@see UnifiedDiffRendererTest} test cases.
 */
final class UnifiedDiffRendererProvider
{
    /**
     * @return iterable<string, array{string, string, int, string}>
     */
    public static function contextCases(): iterable
    {
        yield 'change in the middle with context 0' => [
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n",
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "changed 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n",
            0,
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -5 +5 @@ line 4\n"
            . "-line 5\n"
            . '+changed 5',
        ];
        yield 'change in the middle with context 1' => [
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n",
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "changed 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n",
            1,
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -4,3 +4,3 @@ line 3\n"
            . " line 4\n"
            . "-line 5\n"
            . "+changed 5\n"
            . ' line 6',
        ];
        yield 'two distant hunks with context 0' => [
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n"
            . "line 10\n"
            . "line 11\n"
            . "line 12\n"
            . "line 13\n"
            . "line 14\n"
            . "line 15\n"
            . "line 16\n"
            . "line 17\n"
            . "line 18\n"
            . "line 19\n"
            . "line 20\n",
            "first\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n"
            . "line 10\n"
            . "line 11\n"
            . "line 12\n"
            . "line 13\n"
            . "line 14\n"
            . "line 15\n"
            . "line 16\n"
            . "line 17\n"
            . "seventeen\n"
            . "line 18\n"
            . "line 19\n"
            . "line 20\n",
            0,
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1 +1 @@\n"
            . "-line 1\n"
            . "+first\n"
            . "@@ -17,0 +18 @@ line 17\n"
            . '+seventeen',
        ];
        yield 'two distant hunks with context 1' => [
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n"
            . "line 10\n"
            . "line 11\n"
            . "line 12\n"
            . "line 13\n"
            . "line 14\n"
            . "line 15\n"
            . "line 16\n"
            . "line 17\n"
            . "line 18\n"
            . "line 19\n"
            . "line 20\n",
            "first\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n"
            . "line 10\n"
            . "line 11\n"
            . "line 12\n"
            . "line 13\n"
            . "line 14\n"
            . "line 15\n"
            . "line 16\n"
            . "line 17\n"
            . "seventeen\n"
            . "line 18\n"
            . "line 19\n"
            . "line 20\n",
            1,
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1,2 +1,2 @@\n"
            . "-line 1\n"
            . "+first\n"
            . " line 2\n"
            . "@@ -17,2 +17,3 @@ line 16\n"
            . " line 17\n"
            . "+seventeen\n"
            . ' line 18',
        ];
        yield 'adjacent hunks with context 0' => [
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n"
            . "line 10\n"
            . "line 11\n"
            . "line 12\n"
            . "line 13\n"
            . "line 14\n"
            . "line 15\n",
            "line 1\n"
            . "line 2\n"
            . "X3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n"
            . "X10\n"
            . "line 11\n"
            . "line 12\n"
            . "line 13\n"
            . "line 14\n"
            . "line 15\n",
            0,
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -3 +3 @@ line 2\n"
            . "-line 3\n"
            . "+X3\n"
            . "@@ -10 +10 @@ line 9\n"
            . "-line 10\n"
            . '+X10',
        ];
        yield 'adjacent hunks with context 1' => [
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n"
            . "line 10\n"
            . "line 11\n"
            . "line 12\n"
            . "line 13\n"
            . "line 14\n"
            . "line 15\n",
            "line 1\n"
            . "line 2\n"
            . "X3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n"
            . "X10\n"
            . "line 11\n"
            . "line 12\n"
            . "line 13\n"
            . "line 14\n"
            . "line 15\n",
            1,
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -2,3 +2,3 @@ line 1\n"
            . " line 2\n"
            . "-line 3\n"
            . "+X3\n"
            . " line 4\n"
            . "@@ -9,3 +9,3 @@ line 8\n"
            . " line 9\n"
            . "-line 10\n"
            . "+X10\n"
            . ' line 11',
        ];
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function gitCases(): iterable
    {
        yield 'identical' => [
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n",
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n",
            '',
        ];
        yield 'add at end' => [
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n",
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -4,3 +4,5 @@ line 3\n"
            . " line 4\n"
            . " line 5\n"
            . " line 6\n"
            . "+line 7\n"
            . '+line 8',
        ];
        yield 'delete at start' => [
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n",
            "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1,5 +1,3 @@\n"
            . "-line 1\n"
            . "-line 2\n"
            . " line 3\n"
            . " line 4\n"
            . ' line 5',
        ];
        yield 'change in the middle' => [
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n",
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "changed 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -2,7 +2,7 @@ line 1\n"
            . " line 2\n"
            . " line 3\n"
            . " line 4\n"
            . "-line 5\n"
            . "+changed 5\n"
            . " line 6\n"
            . " line 7\n"
            . ' line 8',
        ];
        yield 'no trailing newline in old' => [
            "a\n"
            . "b\n"
            . 'c',
            "a\n"
            . "b\n"
            . "c\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1,3 +1,3 @@\n"
            . " a\n"
            . " b\n"
            . "-c\n"
            . "\\ No newline at end of file\n"
            . '+c',
        ];
        yield 'no trailing newline in new' => [
            "a\n"
            . "b\n"
            . "c\n",
            "a\n"
            . "b\n"
            . 'c',
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1,3 +1,3 @@\n"
            . " a\n"
            . " b\n"
            . "-c\n"
            . "+c\n"
            . '\\ No newline at end of file',
        ];
        yield 'no trailing newline on both sides' => [
            "a\n"
            . "b\n"
            . 'c',
            "a\n"
            . "b\n"
            . 'd',
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1,3 +1,3 @@\n"
            . " a\n"
            . " b\n"
            . "-c\n"
            . "\\ No newline at end of file\n"
            . "+d\n"
            . '\\ No newline at end of file',
        ];
        yield 'no trailing newline on unchanged last line' => [
            "a\n"
            . "b\n"
            . "c\n"
            . "d\n"
            . "e\n"
            . "f\n"
            . "g\n"
            . 'h',
            "a\n"
            . "B\n"
            . "c\n"
            . "d\n"
            . "e\n"
            . "f\n"
            . "g\n"
            . 'h',
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1,5 +1,5 @@\n"
            . " a\n"
            . "-b\n"
            . "+B\n"
            . " c\n"
            . " d\n"
            . ' e',
        ];
        yield 'two distant hunks' => [
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n"
            . "line 10\n"
            . "line 11\n"
            . "line 12\n"
            . "line 13\n"
            . "line 14\n"
            . "line 15\n"
            . "line 16\n"
            . "line 17\n"
            . "line 18\n"
            . "line 19\n"
            . "line 20\n",
            "first\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n"
            . "line 10\n"
            . "line 11\n"
            . "line 12\n"
            . "line 13\n"
            . "line 14\n"
            . "line 15\n"
            . "line 16\n"
            . "line 17\n"
            . "seventeen\n"
            . "line 18\n"
            . "line 19\n"
            . "line 20\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1,4 +1,4 @@\n"
            . "-line 1\n"
            . "+first\n"
            . " line 2\n"
            . " line 3\n"
            . " line 4\n"
            . "@@ -15,6 +15,7 @@ line 14\n"
            . " line 15\n"
            . " line 16\n"
            . " line 17\n"
            . "+seventeen\n"
            . " line 18\n"
            . " line 19\n"
            . ' line 20',
        ];
        yield 'adjacent hunks merge' => [
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n"
            . "line 10\n"
            . "line 11\n"
            . "line 12\n"
            . "line 13\n"
            . "line 14\n"
            . "line 15\n",
            "line 1\n"
            . "line 2\n"
            . "X3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n"
            . "X10\n"
            . "line 11\n"
            . "line 12\n"
            . "line 13\n"
            . "line 14\n"
            . "line 15\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1,13 +1,13 @@\n"
            . " line 1\n"
            . " line 2\n"
            . "-line 3\n"
            . "+X3\n"
            . " line 4\n"
            . " line 5\n"
            . " line 6\n"
            . " line 7\n"
            . " line 8\n"
            . " line 9\n"
            . "-line 10\n"
            . "+X10\n"
            . " line 11\n"
            . " line 12\n"
            . ' line 13',
        ];
        yield 'hunks seven lines apart stay separate' => [
            "line 1\n"
            . "line 2\n"
            . "line 3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n"
            . "line 10\n"
            . "line 11\n"
            . "line 12\n"
            . "line 13\n"
            . "line 14\n"
            . "line 15\n"
            . "line 16\n",
            "line 1\n"
            . "line 2\n"
            . "X3\n"
            . "line 4\n"
            . "line 5\n"
            . "line 6\n"
            . "line 7\n"
            . "line 8\n"
            . "line 9\n"
            . "line 10\n"
            . "X11\n"
            . "line 12\n"
            . "line 13\n"
            . "line 14\n"
            . "line 15\n"
            . "line 16\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1,6 +1,6 @@\n"
            . " line 1\n"
            . " line 2\n"
            . "-line 3\n"
            . "+X3\n"
            . " line 4\n"
            . " line 5\n"
            . " line 6\n"
            . "@@ -8,7 +8,7 @@ line 7\n"
            . " line 8\n"
            . " line 9\n"
            . " line 10\n"
            . "-line 11\n"
            . "+X11\n"
            . " line 12\n"
            . " line 13\n"
            . ' line 14',
        ];
        yield 'whole file replace' => [
            "alpha\n"
            . "beta\n"
            . "gamma\n",
            "one\n"
            . "two\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1,3 +1,2 @@\n"
            . "-alpha\n"
            . "-beta\n"
            . "-gamma\n"
            . "+one\n"
            . '+two',
        ];
        yield 'empty old' => [
            '',
            "first\n"
            . "second\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -0,0 +1,2 @@\n"
            . "+first\n"
            . '+second',
        ];
        yield 'empty new' => [
            "first\n"
            . "second\n",
            '',
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1,2 +0,0 @@\n"
            . "-first\n"
            . '-second',
        ];
        yield 'repeated identical lines' => [
            "a\n"
            . "b\n"
            . "a\n"
            . "b\n"
            . "c\n",
            "a\n"
            . "b\n"
            . "a\n"
            . "b\n"
            . "a\n"
            . "b\n"
            . "c\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -2,4 +2,6 @@ a\n"
            . " b\n"
            . " a\n"
            . " b\n"
            . "+a\n"
            . "+b\n"
            . ' c',
        ];
        yield 'editorconfig section insert' => [
            "root = true\n"
            . "\n"
            . "[*]\n"
            . "charset = utf-8\n"
            . "end_of_line = lf\n"
            . "indent_size = 4\n"
            . "indent_style = space\n"
            . "insert_final_newline = true\n"
            . "trim_trailing_whitespace = true\n"
            . "\n"
            . "[*.md]\n"
            . "trim_trailing_whitespace = false\n"
            . "\n"
            . "[*.yml]\n"
            . "indent_size = 2\n",
            "root = true\n"
            . "\n"
            . "[*]\n"
            . "charset = utf-8\n"
            . "end_of_line = lf\n"
            . "indent_size = 2\n"
            . "indent_style = space\n"
            . "insert_final_newline = true\n"
            . "trim_trailing_whitespace = true\n"
            . "\n"
            . "[*.md]\n"
            . "trim_trailing_whitespace = false\n"
            . "\n"
            . "[*.json]\n"
            . "indent_size = 2\n"
            . "\n"
            . "[*.yml]\n"
            . "indent_size = 2\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -3,7 +3,7 @@ root = true\n"
            . " [*]\n"
            . " charset = utf-8\n"
            . " end_of_line = lf\n"
            . "-indent_size = 4\n"
            . "+indent_size = 2\n"
            . " indent_style = space\n"
            . " insert_final_newline = true\n"
            . " trim_trailing_whitespace = true\n"
            . "@@ -11,5 +11,8 @@ trim_trailing_whitespace = true\n"
            . " [*.md]\n"
            . " trim_trailing_whitespace = false\n"
            . " \n"
            . "+[*.json]\n"
            . "+indent_size = 2\n"
            . "+\n"
            . " [*.yml]\n"
            . ' indent_size = 2',
        ];
        yield 'function context' => [
            "<?php\n"
            . "\n"
            . "function first()\n"
            . "{\n"
            . "    return 1;\n"
            . "}\n"
            . "\n"
            . "function second()\n"
            . "{\n"
            . "    \$a = 1;\n"
            . "    \$b = 2;\n"
            . "    \$c = 3;\n"
            . "    return \$a;\n"
            . "}\n",
            "<?php\n"
            . "\n"
            . "function first()\n"
            . "{\n"
            . "    return 1;\n"
            . "}\n"
            . "\n"
            . "function second()\n"
            . "{\n"
            . "    \$a = 1;\n"
            . "    \$b = 2;\n"
            . "    \$c = 4;\n"
            . "    return \$a;\n"
            . "}\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -9,6 +9,6 @@ function second()\n"
            . " {\n"
            . "     \$a = 1;\n"
            . "     \$b = 2;\n"
            . "-    \$c = 3;\n"
            . "+    \$c = 4;\n"
            . "     return \$a;\n"
            . ' }',
        ];
        yield 'indent heuristic places new method' => [
            "class A\n"
            . "{\n"
            . "    public function a()\n"
            . "    {\n"
            . "        return 1;\n"
            . "    }\n"
            . "}\n",
            "class A\n"
            . "{\n"
            . "    public function a()\n"
            . "    {\n"
            . "        return 1;\n"
            . "    }\n"
            . "\n"
            . "    public function b()\n"
            . "    {\n"
            . "        return 2;\n"
            . "    }\n"
            . "}\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -4,4 +4,9 @@ class A\n"
            . "     {\n"
            . "         return 1;\n"
            . "     }\n"
            . "+\n"
            . "+    public function b()\n"
            . "+    {\n"
            . "+        return 2;\n"
            . "+    }\n"
            . ' }',
        ];
        yield 'blank line slider' => [
            "x\n"
            . "\n"
            . "y\n"
            . "\n"
            . "z\n",
            "x\n"
            . "\n"
            . "y\n"
            . "\n"
            . "new\n"
            . "\n"
            . "z\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -2,4 +2,6 @@ x\n"
            . " \n"
            . " y\n"
            . " \n"
            . "+new\n"
            . "+\n"
            . ' z',
        ];
        yield 'gitignore entries' => [
            "/vendor\n"
            . "/runtime\n"
            . "\n"
            . "# phpunit\n"
            . "phpunit.xml\n"
            . ".phpunit.cache\n",
            "/vendor\n"
            . "/node_modules\n"
            . "/runtime\n"
            . "\n"
            . "# phpunit\n"
            . "phpunit.xml\n"
            . ".phpunit.result.cache\n"
            . ".phpunit.cache\n"
            . "\n"
            . "# ide\n"
            . ".idea\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1,6 +1,11 @@\n"
            . " /vendor\n"
            . "+/node_modules\n"
            . " /runtime\n"
            . " \n"
            . " # phpunit\n"
            . " phpunit.xml\n"
            . "+.phpunit.result.cache\n"
            . " .phpunit.cache\n"
            . "+\n"
            . "+# ide\n"
            . '+.idea',
        ];
        yield 'function context truncated to 80 bytes' => [
            'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
            . "xxxxxxxxxxxxxxxxxxxx   \n"
            . "1\n"
            . "2\n"
            . "3\n"
            . "4\n"
            . "5\n"
            . "6\n",
            'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
            . "xxxxxxxxxxxxxxxxxxxx   \n"
            . "1\n"
            . "2\n"
            . "3\n"
            . "4\n"
            . "5\n"
            . "7\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . '@@ -4,4 +4,4 @@ xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
            . "xxxxxxxxxxxxxxxx\n"
            . " 3\n"
            . " 4\n"
            . " 5\n"
            . "-6\n"
            . '+7',
        ];
        yield 'indent heuristic moves group up' => [
            "{\n"
            . "  baz\n"
            . "{\n"
            . "qux\n"
            . "}\n"
            . "\n"
            . "qux\n"
            . "bar\n"
            . "}\n"
            . "\n"
            . "foo\n"
            . "  baz\n"
            . "bar\n"
            . "bar\n"
            . "\n"
            . "  baz\n"
            . "foo\n"
            . "\n"
            . "\n"
            . "\n"
            . "qux\n",
            "{\n"
            . "qux\n"
            . "}\n"
            . "\n"
            . "qux\n"
            . "\n"
            . "bar\n"
            . "}\n"
            . "\n"
            . "foo\n"
            . "  baz\n"
            . "bar\n"
            . "bar\n"
            . "\n"
            . "  baz\n"
            . "foo\n"
            . "\n"
            . "\n"
            . "\n"
            . 'qux',
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1,10 +1,9 @@\n"
            . "-{\n"
            . "-  baz\n"
            . " {\n"
            . " qux\n"
            . " }\n"
            . " \n"
            . " qux\n"
            . "+\n"
            . " bar\n"
            . " }\n"
            . " \n"
            . "@@ -18,4 +17,4 @@ foo\n"
            . " \n"
            . " \n"
            . " \n"
            . "-qux\n"
            . "+qux\n"
            . '\\ No newline at end of file',
        ];
        yield 'multi-matched lines discarded before alignment' => [
            "[sect]\n"
            . "    qux\n"
            . "{\n"
            . "  \n"
            . "end\n"
            . "bar\n"
            . "# c\n"
            . "    qux\n"
            . "}\n"
            . "\t\tdeep\n"
            . "  baz\n"
            . "  \n"
            . "    qux\n"
            . "foo\n"
            . "bar\n"
            . "x = 1\n"
            . "\t\tdeep\n"
            . "bar\n"
            . "{\n"
            . "bar\n"
            . "end\n"
            . "    qux\n"
            . "{\n"
            . "bar\n"
            . "foo\n"
            . "end\n"
            . "[sect]\n"
            . "foo\n"
            . '  baz',
            "[sect]\n"
            . "    qux\n"
            . "    qux\n"
            . "}\n"
            . "\t\tdeep\n"
            . "  baz\n"
            . "  \n"
            . "    qux\n"
            . "}\n"
            . "    qux\n"
            . "bar\n"
            . "foo\n"
            . "bar\n"
            . "\t\tdeep\n"
            . "bar\n"
            . "{\n"
            . "bar\n"
            . "end\n"
            . "    qux\n"
            . "{\n"
            . "foo\n"
            . "  baz\n",
            "--- a/a\n"
            . "+++ b/b\n"
            . "@@ -1,19 +1,16 @@\n"
            . " [sect]\n"
            . "     qux\n"
            . "-{\n"
            . "-  \n"
            . "-end\n"
            . "-bar\n"
            . "-# c\n"
            . "     qux\n"
            . " }\n"
            . " \t\tdeep\n"
            . "   baz\n"
            . "   \n"
            . "     qux\n"
            . "+}\n"
            . "+    qux\n"
            . "+bar\n"
            . " foo\n"
            . " bar\n"
            . "-x = 1\n"
            . " \t\tdeep\n"
            . " bar\n"
            . " {\n"
            . "@@ -21,9 +18,5 @@ bar\n"
            . " end\n"
            . "     qux\n"
            . " {\n"
            . "-bar\n"
            . "-foo\n"
            . "-end\n"
            . "-[sect]\n"
            . " foo\n"
            . "-  baz\n"
            . "\\ No newline at end of file\n"
            . '+  baz',
        ];
    }

    /**
     * @return iterable<string, array{string, string, list<DiffLine>}>
     */
    public static function mismatchedScripts(): iterable
    {
        yield 'empty script' => [
            "a\n",
            "b\n",
            [],
        ];
        yield 'fewer lines than both texts' => [
            "a\nb\nc\n",
            "a\nb\nd\n",
            [new DiffLine(DiffOp::Context, 'a')],
        ];
        yield 'fewer lines than the new text' => [
            "a\n",
            "b\n",
            [new DiffLine(DiffOp::Remove, 'a')],
        ];
        yield 'fewer lines than the old text' => [
            "a\n",
            "b\n",
            [new DiffLine(DiffOp::Add, 'b')],
        ];
        yield 'more additions than new lines' => [
            "a\n",
            "b\n",
            [new DiffLine(DiffOp::Add, 'b'), new DiffLine(DiffOp::Add, 'b')],
        ];
        yield 'more removals than old lines' => [
            "a\n",
            "b\n",
            [new DiffLine(DiffOp::Remove, 'a'), new DiffLine(DiffOp::Remove, 'a')],
        ];
    }
}
