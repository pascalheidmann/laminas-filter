<?php

declare(strict_types=1);

namespace LaminasTest\Filter;

use Laminas\Filter\StringTrim;
use PHPUnit\Framework\TestCase;
use stdClass;

use function utf8_encode;

class StringTrimTest extends TestCase
{
    // @codingStandardsIgnoreStart
    /**
     * @var StringTrim
     */
    protected $_filter;
    // @codingStandardsIgnoreEnd

    /**
     * Creates a new Laminas\Filter\StringTrim object for each test method
     */
    public function setUp(): void
    {
        $this->_filter = new StringTrim();
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
            'string' => 'string',
            ' str '  => 'str',
            "\ns\t"  => 's',
        ];
        foreach ($valuesExpected as $input => $output) {
            $this->assertEquals($output, $filter($input));
        }
    }

    /**
     * Ensures that the filter follows expected behavior
     *
     * @return void
     */
    public function testUtf8()
    {
        $this->assertEquals('a', $this->_filter->filter(utf8_encode("\xa0a\xa0")));
    }

    /**
     * Ensures that getCharList() returns expected default value
     *
     * @return void
     */
    public function testGetCharList()
    {
        $this->assertEquals(null, $this->_filter->getCharList());
    }

    /**
     * Ensures that setCharList() follows expected behavior
     *
     * @return void
     */
    public function testSetCharList()
    {
        $this->_filter->setCharList('&');
        $this->assertEquals('&', $this->_filter->getCharList());
    }

    /**
     * Ensures expected behavior under custom character list
     *
     * @return void
     */
    public function testCharList()
    {
        $filter = $this->_filter;
        $filter->setCharList('&');
        $this->assertEquals('a&b', $filter('&&a&b&&'));
    }

    /**
     * @group Laminas-7183
     *
     * @return void
     */
    public function testLaminas7183(): void
    {
        $filter = $this->_filter;
        $this->assertEquals('Зенд', $filter('Зенд'));
    }

    /**
     * @group Laminas-170
     *
     * @return void
     */
    public function testLaminas170(): void
    {
        $filter = $this->_filter;
        $this->assertEquals('Расчет', $filter('Расчет'));
    }

    /**
     * @group Laminas-7902
     *
     * @return void
     */
    public function testLaminas7902(): void
    {
        $filter = $this->_filter;
        $this->assertEquals('/', $filter('/'));
    }

    /**
     * @group Laminas-10891
     */
    public function testLaminas10891(): void
    {
        $filter = $this->_filter;
        $this->assertEquals('Зенд', $filter('   Зенд   '));
        $this->assertEquals('Зенд', $filter('Зенд   '));
        $this->assertEquals('Зенд', $filter('   Зенд'));

        $trimCharList = " \t\n\r\x0B・。";
        $filter       = new StringTrim($trimCharList);
        $this->assertEquals('Зенд', $filter->filter('。  Зенд  。'));
    }

    /**
     * @return array[]
     */
    public function getNonStringValues(): array
    {
        return [
            [1],
            [1.0],
            [true],
            [false],
            [null],
            [[1, 2, 3]],
            [new stdClass()],
        ];
    }

    /**
     * @dataProvider getNonStringValues
     *
     * @param mixed $value
     *
     * @return void
     */
    public function testShouldNotFilterNonStringValues($value): void
    {
        $filtered = $this->_filter->filter($value);
        $this->assertSame($value, $filtered);
    }

    /**
     * Ensures expected behavior with '0' as character list
     *
     * @group 6261
     *
     * @return void
     */
    public function testEmptyCharList(): void
    {
        $filter = $this->_filter;
        $filter->setCharList('0');
        $this->assertEquals('a0b', $filter('00a0b00'));

        $filter->setCharList('');
        $this->assertEquals('str', $filter(' str '));
    }
}
