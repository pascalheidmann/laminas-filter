<?php

declare(strict_types=1);

namespace LaminasTest\Filter;

use Laminas\Filter\ToInt as ToIntFilter;
use PHPUnit\Framework\TestCase;
use stdClass;

class ToIntTest extends TestCase
{
    public function itShouldFollowTheExpectedBehaviourTest(): void
    {
        $filter = new ToIntFilter();

        $valuesExpected = [
            'string' => 0,
            '1'      => 1,
            '-1'     => -1,
            '1.1'    => 1,
            '-1.1'   => -1,
            '0.9'    => 0,
            '-0.9'   => 0,
        ];
        foreach ($valuesExpected as $input => $output) {
            $this->assertEquals($output, $filter($input));
        }
    }

    /**
     * @return array<int, ?stdClass|array{0: string, 1: int}>
     */
    public function returnUnfilteredDataProvider(): array
    {
        return [
            [null],
            [new stdClass()],
            [
                [
                    '1',
                    -1,
                ],
            ],
        ];
    }

    /**
     * @dataProvider returnUnfilteredDataProvider
     * @param null|stdClass|array{0: string, 1: int} $input
     */
    public function testReturnUnfiltered($input): void
    {
        $filter = new ToIntFilter();

        $this->assertEquals($input, $filter($input));
    }
}
