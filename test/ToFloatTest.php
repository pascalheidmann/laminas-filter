<?php

declare(strict_types=1);

namespace LaminasTest\Filter;

use Laminas\Filter\ToFloat as ToFloatFilter;
use PHPUnit\Framework\TestCase;
use stdClass;

class ToFloatTest extends TestCase
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function filterableValuesProvider(): array
    {
        return [
            'string word' => ['string', 0],
            'string 1'    => ['1', 1],
            'string -1'   => ['-1', -1],
            'string 1.1'  => ['1.1', 1.1],
            'string -1.1' => ['-1.1', -1.1],
            'string 0.9'  => ['0.9', 0.9],
            'string -0.9' => ['-0.9', -0.9],
            'integer 1'   => [1, 1.0],
            'integer -1'  => [-1, -1.0],
            'true'        => [true, 1.0],
            'false'       => [false, 0.0],
            'float 1.1'   => [1.1, 1.1],
        ];
    }

    /**
     * Ensures that the filter follows expected behavior
     *
     * @dataProvider filterableValuesProvider
     * @param mixed $input
     * @param string $expectedOutput
     */
    public function testCanFilterScalarValuesAsExpected($input, $expectedOutput)
    {
        $filter = new ToFloatFilter();
        $this->assertEquals($expectedOutput, $filter($input));
    }

    /**
     * @return array<string, array{0: null|array{0: string, 1: int}|stdClass}>
     */
    public function unfilterableValuesProvider(): array
    {
        return [
            'null'   => [null],
            'array'  => [
                [
                    '1',
                    -1,
                ],
            ],
            'object' => [new stdClass()],
        ];
    }

    /**
     * @dataProvider unfilterableValuesProvider
     * @param null|array{0: string, 1: int}|stdClass $input
     */
    public function testReturnsUnfilterableInputVerbatim($input): void
    {
        $filter = new ToFloatFilter();
        $this->assertEquals($input, $filter($input));
    }
}
