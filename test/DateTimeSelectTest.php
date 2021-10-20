<?php

declare(strict_types=1);

namespace LaminasTest\Filter;

use Laminas\Filter\DateTimeSelect as DateTimeSelectFilter;
use Laminas\Filter\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

class DateTimeSelectTest extends TestCase
{
    /**
     * @dataProvider provideFilter
     *
     * @param array $options
     * @param array $input
     * @param string | null $expected
     *
     * @return void
     */
    public function testFilter($options, $input, $expected): void
    {
        $sut = new DateTimeSelectFilter();
        $sut->setOptions($options);
        $this->assertEquals($expected, $sut->filter($input));
    }

    public function provideFilter(): array
    {
        return [
            [
                [],
                ['year' => '2014', 'month' => '10', 'day' => '26', 'hour' => '12', 'minute' => '35'],
                '2014-10-26 12:35:00',
            ],
            [
                ['nullOnEmpty' => true],
                ['year' => null, 'month' => '10', 'day' => '26', 'hour' => '12', 'minute' => '35'],
                null,
            ],
            [
                ['null_on_empty' => true],
                ['year' => null, 'month' => '10', 'day' => '26', 'hour' => '12', 'minute' => '35'],
                null,
            ],
            [
                ['nullOnAllEmpty' => true],
                ['year' => null, 'month' => null, 'day' => null, 'hour' => null, 'minute' => null],
                null,
            ],
            [
                ['null_on_all_empty' => true],
                ['year' => null, 'month' => null, 'day' => null, 'hour' => null, 'minute' => null],
                null,
            ],
        ];
    }

    public function testInvalidInput(): void
    {
        $this->expectException(RuntimeException::class);
        $sut = new DateTimeSelectFilter();
        $sut->filter(['year' => '2120', 'month' => '10', 'day' => '26', 'hour' => '12']);
    }
}
