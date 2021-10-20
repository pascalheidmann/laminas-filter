<?php

declare(strict_types=1);

namespace LaminasTest\Filter;

use Laminas\Filter\Exception;
use Laminas\Filter\StringToUpper as StringToUpperFilter;
use PHPUnit\Framework\TestCase;
use stdClass;

use function function_exists;
use function mb_internal_encoding;

class StringToUpperTest extends TestCase
{
    // @codingStandardsIgnoreStart
    /**
     * Laminas_Filter_StringToLower object
     *
     * @var StringToUpperFilter
     */
    protected $_filter;
    // @codingStandardsIgnoreEnd

    /**
     * Creates a new Laminas_Filter_StringToUpper object for each test method
     */
    public function setUp(): void
    {
        $this->_filter = new StringToUpperFilter();
    }

    /**
     * Ensures that the filter follows expected behavior
     *
     * @return void
     */
    public function testBasic()
    {
        $filter         = $this->_filter;
        $valuesExpected = [
            'STRING' => 'STRING',
            'ABC1@3' => 'ABC1@3',
            'A b C'  => 'A B C',
        ];

        foreach ($valuesExpected as $input => $output) {
            $this->assertEquals($output, $filter($input));
        }
    }

    /**
     * Ensures that the filter follows expected behavior with
     * specified encoding
     *
     * @return void
     */
    public function testWithEncoding()
    {
        $filter         = $this->_filter;
        $valuesExpected = [
            'ü'     => 'Ü',
            'ñ'     => 'Ñ',
            'üñ123' => 'ÜÑ123',
        ];

        try {
            $filter->setEncoding('UTF-8');
            foreach ($valuesExpected as $input => $output) {
                $this->assertEquals($output, $filter($input));
            }
        } catch (Exception\ExtensionNotLoadedException $e) {
            $this->assertContains('mbstring is required', $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function testFalseEncoding()
    {
        if (! function_exists('mb_strtolower')) {
            $this->markTestSkipped('mbstring required');
        }

        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not supported');
        $this->_filter->setEncoding('aaaaa');
    }

    /**
     * @Laminas-8989
     *
     * @return void
     */
    public function testInitiationWithEncoding(): void
    {
        $valuesExpected = [
            'ü'     => 'Ü',
            'ñ'     => 'Ñ',
            'üñ123' => 'ÜÑ123',
        ];

        try {
            $filter = new StringToUpperFilter(['encoding' => 'UTF-8']);
            foreach ($valuesExpected as $input => $output) {
                $this->assertEquals($output, $filter($input));
            }
        } catch (Exception\ExtensionNotLoadedException $e) {
            $this->assertContains('mbstring is required', $e->getMessage());
        }
    }

    /**
     * @Laminas-9058
     *
     * @return void
     */
    public function testCaseInsensitiveEncoding(): void
    {
        $filter         = $this->_filter;
        $valuesExpected = [
            'ü'     => 'Ü',
            'ñ'     => 'Ñ',
            'üñ123' => 'ÜÑ123',
        ];

        try {
            $filter->setEncoding('UTF-8');
            foreach ($valuesExpected as $input => $output) {
                $this->assertEquals($output, $filter($input));
            }

            $this->_filter->setEncoding('utf-8');
            foreach ($valuesExpected as $input => $output) {
                $this->assertEquals($output, $filter($input));
            }

            $this->_filter->setEncoding('UtF-8');
            foreach ($valuesExpected as $input => $output) {
                $this->assertEquals($output, $filter($input));
            }
        } catch (Exception\ExtensionNotLoadedException $e) {
            $this->assertContains('mbstring is required', $e->getMessage());
        }
    }

    /**
     * @group Laminas-9854
     *
     * @return void
     */
    public function testDetectMbInternalEncoding(): void
    {
        if (! function_exists('mb_internal_encoding')) {
            $this->markTestSkipped("Function 'mb_internal_encoding' not available");
        }

        $this->assertEquals(mb_internal_encoding(), $this->_filter->getEncoding());
    }

    /**
     * @return array<array{0: null|stdClass|string[]}>
     */
    public function returnUnfilteredDataProvider(): array
    {
        return [
            [null],
            [new stdClass()],
            [
                [
                    'lower case written',
                    'This should stay the same',
                ],
            ],
        ];
    }

    /**
     * @dataProvider returnUnfilteredDataProvider
     * @param null|stdClass|string[] $input
     */
    public function testReturnUnfiltered($input): void
    {
        $this->assertEquals($input, $this->_filter->filter($input));
    }

    /**
     * @group 7147
     *
     * @return void
     */
    public function testFilterUsesGetEncodingMethod(): void
    {
        $filterMock = $this->getMockBuilder(StringToUpperFilter::class)
            ->setMethods(['getEncoding'])
            ->getMock();
        $filterMock->expects($this->once())
            ->method('getEncoding')
            ->with();
        $filterMock->filter('foo');
    }
}
