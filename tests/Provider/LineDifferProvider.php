<?php

declare(strict_types=1);

namespace PHPForge\Diff\Tests\Provider;

use PHPForge\Diff\Tests\Unit\LineDifferTest;
use RuntimeException;

use function file_get_contents;
use function implode;
use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * Data provider for {@see LineDifferTest} test cases.
 */
final class LineDifferProvider
{
    /**
     * Each operation string holds one character per script line: `=` kept, `-` removed, `+` added.
     *
     * @return iterable<string, array{string, string, string}>
     */
    public static function gitCases(): iterable
    {
        $content = file_get_contents(__DIR__ . '/../Fixtures/git-cases.json');

        if ($content === false) {
            throw new RuntimeException('Could not read the Git cases fixture.');
        }

        $cases = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($cases)) {
            throw new RuntimeException('The Git cases fixture must decode to a list.');
        }

        /** @var array{name: string, old: list<string>, new: list<string>, ops: string} $case */
        foreach ($cases as $case) {
            yield $case['name'] => [implode("\n", $case['old']), implode("\n", $case['new']), $case['ops']];
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function scenarios(): iterable
    {
        yield 'all different' => [
            "a\nb",
            "x\ny",
        ];
        yield 'append' => [
            "a\nb",
            "a\nb\nc",
        ];
        yield 'both empty' => ['', ''];
        yield 'duplicate lines' => [
            "a\na\na",
            "a\na",
        ];
        yield 'empty new' => [
            "a\nb",
            '',
        ];
        yield 'empty old' => [
            '',
            "a\nb",
        ];
        yield 'identical' => [
            "a\nb\nc",
            "a\nb\nc",
        ];
        yield 'interleaved blocks' => [
            "\n{\n  a\n}\n\nx\nx\ny\n\n{\n  b\n}\nz\nz",
            "{\n  a\n  c\n}\n\ny\nx\n\n\n{\n}\nz\nw\nz\n",
        ];
        yield 'loosely equal numeric strings' => [
            '1',
            '01',
        ];
        yield 'numeric string with leading space' => [
            '1',
            ' 1',
        ];
        yield 'numeric strings in scientific form' => [
            "1\n10",
            "01\n1e1",
        ];
        yield 'prepend' => [
            "b\nc",
            "a\nb\nc",
        ];
        yield 'remove middle' => [
            "a\nb\nc",
            "a\nc",
        ];
        yield 'reorder' => [
            "a\nb\nc",
            "c\nb\na",
        ];
        yield 'single change' => [
            "a\nb\nc",
            "a\nX\nc",
        ];
    }

    /**
     * @return iterable<string, array{string, string, list<string>}>
     */
    public static function scripts(): iterable
    {
        yield 'all different' => [
            "a\nb",
            "x\ny",
            ['- a', '- b', '+ x', '+ y'],
        ];
        yield 'append' => [
            "a\nb",
            "a\nb\nc",
            ['  a', '  b', '+ c'],
        ];
        yield 'blank lines' => [
            "a\n\nb",
            "a\nb\n",
            ['  a', '- ', '  b', '+ '],
        ];
        yield 'both empty' => [
            '',
            '',
            ['  '],
        ];
        yield 'crossing duplicates' => [
            "b\nb\na\nc",
            "b\nc\nb\na",
            ['  b', '+ c', '  b', '  a', '- c'],
        ];
        yield 'delete at both ends' => [
            "a\nb\nc",
            'b',
            ['- a', '  b', '- c'],
        ];
        yield 'distance two with leading context' => [
            "a\nb\nc",
            "a\nc\nb",
            ['  a', '- b', '  c', '+ b'],
        ];
        yield 'duplicate lines' => [
            "a\na\na",
            "a\na",
            ['  a', '  a', '- a'],
        ];
        yield 'duplicated block' => [
            "x\ny\nx\ny",
            "x\ny",
            ['  x', '  y', '- x', '- y'],
        ];
        yield 'empty new' => [
            "a\nb",
            '',
            ['- a', '- b', '+ '],
        ];
        yield 'empty old' => [
            '',
            "a\nb",
            ['- ', '+ a', '+ b'],
        ];
        yield 'full reversal of four' => [
            "a\nb\nc\nd",
            "d\nc\nb\na",
            ['- a', '- b', '- c', '  d', '+ c', '+ b', '+ a'],
        ];
        yield 'identical' => [
            "a\nb\nc",
            "a\nb\nc",
            ['  a', '  b', '  c'],
        ];
        yield 'insert at both ends' => [
            'b',
            "a\nb\nc",
            ['+ a', '  b', '+ c'],
        ];
        yield 'insert before delete' => [
            "b\nc",
            "a\nb",
            ['+ a', '  b', '- c'],
        ];
        yield 'interleaved changes' => [
            "a\nb\nc\nd\ne\nf\ng\nh",
            "a\nX\nc\nY\ne\nZ\ng\nh",
            ['  a', '- b', '+ X', '  c', '- d', '+ Y', '  e', '- f', '+ Z', '  g', '  h'],
        ];
        yield 'many lines against one' => [
            "a\nm\nb\nm",
            'm',
            ['- a', '- m', '- b', '  m'],
        ];
        yield 'match only in left a-half' => [
            "x\nz",
            'x',
            ['  x', '- z'],
        ];
        yield 'mixed lines #1' => [
            "c\nc\nc\nb\nb\na\nb",
            "c\nb",
            ['  c', '- c', '- c', '- b', '- b', '- a', '  b'],
        ];
        yield 'mixed lines #10' => [
            "a\nc\nb\nb\na\nb",
            "a\nc\na\nb",
            ['  a', '  c', '- b', '- b', '  a', '  b'],
        ];
        yield 'mixed lines #2' => [
            "c\nb\nb\na\na",
            "a\nb\na\nb\nc\nc",
            ['- c', '- b', '- b', '  a', '+ b', '  a', '+ b', '+ c', '+ c'],
        ];
        yield 'mixed lines #3' => [
            "b\nb\nb",
            "b\nc",
            ['  b', '- b', '- b', '+ c'],
        ];
        yield 'mixed lines #4' => [
            "b\nc\nc",
            "a\nc\nb\na\na\na",
            ['- b', '- c', '+ a', '  c', '+ b', '+ a', '+ a', '+ a'],
        ];
        yield 'mixed lines #5' => [
            "c\nc",
            "a\nc\na\na",
            ['+ a', '  c', '- c', '+ a', '+ a'],
        ];
        yield 'mixed lines #6' => [
            "b\na",
            "b\nb\na\nb\nb\nb",
            ['  b', '+ b', '  a', '+ b', '+ b', '+ b'],
        ];
        yield 'mixed lines #7' => [
            "c\nc\nb\nb",
            "c\na\na\nb\nc\nb\nb",
            ['  c', '+ a', '+ a', '+ b', '  c', '  b', '  b'],
        ];
        yield 'mixed lines #8' => [
            "b\nb\nb\na\na\nc",
            "c\nb",
            ['- b', '- b', '- b', '- a', '- a', '  c', '+ b'],
        ];
        yield 'mixed lines #9' => [
            "a\na\nb",
            "b\na\na\na\nb",
            ['+ b', '+ a', '  a', '  a', '  b'],
        ];
        yield 'numeric lines' => [
            "1\n01\n123",
            "1\n1.0\n123",
            ['  1', '- 01', '+ 1.0', '  123'],
        ];
        yield 'one line against many' => [
            'm',
            "a\nm\nb\nm",
            ['+ a', '+ m', '+ b', '  m'],
        ];
        yield 'prepend' => [
            "b\nc",
            "a\nb\nc",
            ['+ a', '  b', '  c'],
        ];
        yield 'remove middle' => [
            "a\nb\nc",
            "a\nc",
            ['  a', '- b', '  c'],
        ];
        yield 'reorder' => [
            "a\nb\nc",
            "c\nb\na",
            ['- a', '- b', '  c', '+ b', '+ a'],
        ];
        yield 'repeated delimiters' => [
            "{\na\n}\n{\nb\n}",
            "{\nb\n}\n{\na\n}",
            ['  {', '- a', '+ b', '  }', '  {', '- b', '+ a', '  }'],
        ];
        yield 'repeated lines insert after the last copy' => [
            "a\na\na\nb",
            "a\na\na\na\nb",
            ['  a', '  a', '  a', '+ a', '  b'],
        ];
        yield 'rotation' => [
            "a\nb\nc",
            "c\na\nb",
            ['+ c', '  a', '  b', '- c'],
        ];
        yield 'shared prefix and suffix' => [
            "h\nA\nB\nt",
            "h\nC\nt",
            ['  h', '- A', '- B', '+ C', '  t'],
        ];
        yield 'shared prefix only' => [
            "a\nb\nc",
            "a\nb\nX\nY",
            ['  a', '  b', '- c', '+ X', '+ Y'],
        ];
        yield 'shared suffix only' => [
            "X\nb\nc",
            "Y\nZ\nb\nc",
            ['- X', '+ Y', '+ Z', '  b', '  c'],
        ];
        yield 'single change' => [
            "a\nb\nc",
            "a\nX\nc",
            ['  a', '- b', '+ X', '  c'],
        ];
        yield 'single line different' => [
            'a',
            'b',
            ['- a', '+ b'],
        ];
        yield 'single line equal' => [
            'a',
            'a',
            ['  a'],
        ];
        yield 'swap' => [
            "a\nb",
            "b\na",
            ['- a', '  b', '+ a'],
        ];
    }
}
