<?php

/**
 * @see https://github.com/laminas/laminas-filter for the canonical source repository
 */

declare(strict_types=1);

namespace LaminasTest\Filter;

use Laminas\Filter\BaseName as BaseNameFilter;
use PHPUnit\Framework\TestCase;
use stdClass;

class BaseNameTest extends TestCase
{
    /**
     * Ensures that the filter follows expected behavior
     */
    public function testBasic(): void
    {
        $filter         = new BaseNameFilter();
        $valuesExpected = [
            '/path/to/filename'     => 'filename',
            '/path/to/filename.ext' => 'filename.ext',
        ];
        foreach ($valuesExpected as $input => $output) {
            $this->assertEquals($output, $filter($input));
        }
    }

    /**
     * @return (null|stdClass|string[])[][]
     *
     * @psalm-return array{0: array{0: null}, 1: array{0: stdClass}, 2: array{0: array{0: '/path/to/filename', 1: '/path/to/filename.ext'}}}
     */
    public function returnUnfilteredDataProvider(): array
    {
        return [
            [null],
            [new stdClass()],
            [
                [
                    '/path/to/filename',
                    '/path/to/filename.ext',
                ],
            ],
        ];
    }

    /**
     * @dataProvider returnUnfilteredDataProvider
     * @param null|stdClass|array<int, string[]> $input
     */
    public function testReturnUnfiltered($input): void
    {
        $filter = new BaseNameFilter();

        $this->assertEquals($input, $filter($input));
    }
}
