<?php

declare(strict_types=1);

namespace LaminasTest\Filter;

use Laminas\Filter\FilterPluginManager;
use Laminas\Filter\Whitelist as WhitelistFilter;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayObject;
use Laminas\Stdlib\Exception;
use PHPUnit\Framework\TestCase;

use function gettype;
use function sprintf;
use function var_export;

class WhitelistTest extends TestCase
{
    public function testConstructorOptions()
    {
        $filter = new WhitelistFilter([
            'list'   => ['test', 1],
            'strict' => true,
        ]);

        $this->assertEquals(true, $filter->getStrict());
        $this->assertEquals(['test', 1], $filter->getList());
    }

    public function testConstructorDefaults()
    {
        $filter = new WhitelistFilter();

        $this->assertEquals(false, $filter->getStrict());
        $this->assertEquals([], $filter->getList());
    }

    public function testWithPluginManager()
    {
        $pluginManager = new FilterPluginManager(new ServiceManager());
        $filter        = $pluginManager->get('whitelist');

        $this->assertInstanceOf(WhitelistFilter::class, $filter);
    }

    public function testNullListShouldThrowException()
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $filter = new WhitelistFilter([
            'list' => null,
        ]);
    }

    public function testTraversableConvertsToArray()
    {
        $array  = ['test', 1];
        $obj    = new ArrayObject(['test', 1]);
        $filter = new WhitelistFilter([
            'list' => $obj,
        ]);
        $this->assertEquals($array, $filter->getList());
    }

    public function testSetStrictShouldCastToBoolean()
    {
        $filter = new WhitelistFilter([
            'strict' => 1,
        ]);
        $this->assertSame(true, $filter->getStrict());
    }

    /**
     * @param mixed $value
     * @param bool  $expected
     * @dataProvider defaultTestProvider
     */
    public function testDefault($value, $expected)
    {
        $filter = new WhitelistFilter();
        $this->assertSame($expected, $filter->filter($value));
    }

    /**
     * @param array<int, mixed> $testData
     * @dataProvider listTestProvider
     */
    public function testList(bool $strict, array $list, $testData): void
    {
        $filter = new WhitelistFilter([
            'strict' => $strict,
            'list'   => $list,
        ]);
        foreach ($testData as $data) {
            [$value, $expected] = $data;
            $message            = sprintf(
                '%s (%s) is not filtered as %s; type = %s',
                var_export($value, true),
                gettype($value),
                var_export($expected, true),
                $strict
            );
            $this->assertSame($expected, $filter->filter($value), $message);
        }
    }

    /**
     * @return array{0: mixed, 1: null}
     */
    public static function defaultTestProvider(): array
    {
        return [
            ['test',   null],
            [0,        null],
            [0.1,      null],
            [[], null],
            [null,     null],
        ];
    }

    /**
     * @return array[]
     */
    public static function listTestProvider(): array
    {
        return [
            [
                true, //strict
                ['test', 0],
                [
                    ['test',   'test'],
                    [0,        0],
                    [null,     null],
                    [false,    null],
                    [0.0,      null],
                    [[], null],
                ],
            ],
            [
                false, //not strict
                ['test', 0],
                [
                    ['test',   'test'],
                    [0,        0],
                    [null,     null],
                    [false,    false],
                    [0.0,      0.0],
                    [0.1,      null],
                    [[], null],
                ],
            ],
        ];
    }
}
