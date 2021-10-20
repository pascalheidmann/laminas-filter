<?php

declare(strict_types=1);

namespace LaminasTest\Filter;

use ArrayIterator;
use Laminas\Filter\FilterChain;
use Laminas\Filter\PregReplace;
use Laminas\Filter\StringToLower;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use PHPUnit\Framework\TestCase;

use function count;
use function function_exists;
use function serialize;
use function strtolower;
use function strtoupper;
use function trim;
use function unserialize;

class FilterChainTest extends TestCase
{
    public function testEmptyFilterChainReturnsOriginalValue(): void
    {
        $chain = new FilterChain();
        $value = 'something';
        $this->assertEquals($value, $chain->filter($value));
    }

    public function testFiltersAreExecutedInFifoOrder(): void
    {
        $chain = new FilterChain();
        $chain->attach(new TestAsset\LowerCase())
            ->attach(new TestAsset\StripUpperCase());
        $value         = 'AbC';
        $valueExpected = 'abc';
        $this->assertEquals($valueExpected, $chain->filter($value));
    }

    public function testFiltersAreExecutedAccordingToPriority(): void
    {
        $chain = new FilterChain();
        $chain->attach(new TestAsset\StripUpperCase())
            ->attach(new TestAsset\LowerCase(), 100);
        $value         = 'AbC';
        $valueExpected = 'b';
        $this->assertEquals($valueExpected, $chain->filter($value));
    }

    public function testAllowsConnectingArbitraryCallbacks(): void
    {
        $chain = new FilterChain();
        $chain->attach(function ($value) {
            return strtolower($value);
        });
        $value = 'AbC';
        $this->assertEquals('abc', $chain->filter($value));
    }

    public function testAllowsConnectingViaClassShortName(): void
    {
        if (! function_exists('mb_strtolower')) {
            $this->markTestSkipped('mbstring required');
        }

        $chain = new FilterChain();
        $chain->attachByName(StringTrim::class, null, 100)
            ->attachByName(StripTags::class)
            ->attachByName(StringToLower::class, ['encoding' => 'utf-8'], 900);

        $value         = '<a name="foo"> ABC </a>';
        $valueExpected = 'abc';
        $this->assertEquals($valueExpected, $chain->filter($value));
    }

    public function testAllowsConfiguringFilters(): void
    {
        $config = $this->getChainConfig();
        $chain  = new FilterChain();
        $chain->setOptions($config);
        $value         = '<a name="foo"> abc </a><img id="bar" />';
        $valueExpected = 'ABC <IMG ID="BAR" />';
        $this->assertEquals($valueExpected, $chain->filter($value));
    }

    public function testAllowsConfiguringFiltersViaConstructor(): void
    {
        $config        = $this->getChainConfig();
        $chain         = new FilterChain($config);
        $value         = '<a name="foo"> abc </a>';
        $valueExpected = 'ABC';
        $this->assertEquals($valueExpected, $chain->filter($value));
    }

    public function testConfigurationAllowsTraversableObjects(): void
    {
        $config        = $this->getChainConfig();
        $config        = new ArrayIterator($config);
        $chain         = new FilterChain($config);
        $value         = '<a name="foo"> abc </a>';
        $valueExpected = 'ABC';
        $this->assertEquals($valueExpected, $chain->filter($value));
    }

    public function testCanRetrieveFilterWithUndefinedConstructor(): void
    {
        $chain    = new FilterChain([
            'filters' => [
                ['name' => 'int'],
            ],
        ]);
        $filtered = $chain->filter('127.1');
        $this->assertEquals(127, $filtered);
    }

    /**
     * @return array<string, array>
     */
    protected function getChainConfig(): array
    {
        return [
            'callbacks' => [
                ['callback' => self::class . '::staticUcaseFilter'],
                [
                    'priority' => 10000,
                    'callback' => function ($value) {
                        return trim($value);
                    },
                ],
            ],
            'filters'   => [
                [
                    'name'     => StripTags::class,
                    'options'  => ['allowTags' => 'img', 'allowAttribs' => 'id'],
                    'priority' => 10100,
                ],
            ],
        ];
    }

    public static function staticUcaseFilter(string $value): string
    {
        return strtoupper($value);
    }

    /**
     * @group Laminas-412
     *
     * @return void
     */
    public function testCanAttachMultipleFiltersOfTheSameTypeAsDiscreteInstances(): void
    {
        $chain = new FilterChain();
        $chain->attachByName(PregReplace::class, [
            'pattern'     => '/Foo/',
            'replacement' => 'Bar',
        ]);
        $chain->attachByName(PregReplace::class, [
            'pattern'     => '/Bar/',
            'replacement' => 'PARTY',
        ]);

        $this->assertEquals(2, count($chain));
        $filters = $chain->getFilters();
        $compare = null;
        foreach ($filters as $filter) {
            $this->assertNotSame($compare, $filter);
            $compare = $filter;
        }

        $this->assertEquals('Tu et PARTY', $chain->filter('Tu et Foo'));
    }

    public function testClone(): void
    {
        $chain = new FilterChain();
        $clone = clone $chain;

        $chain->attachByName(StripTags::class);

        $this->assertCount(0, $clone);
    }

    public function testCanSerializeFilterChain(): void
    {
        $chain = new FilterChain();
        $chain->attach(new TestAsset\LowerCase())
            ->attach(new TestAsset\StripUpperCase());
        $serialized = serialize($chain);

        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(FilterChain::class, $unserialized);
        $this->assertEquals(2, count($unserialized));
        $value         = 'AbC';
        $valueExpected = 'abc';
        $this->assertEquals($valueExpected, $unserialized->filter($value));
    }

    public function testMergingTwoFilterChainsKeepFiltersPriority(): void
    {
        $value         = 'AbC';
        $valueExpected = 'abc';

        $chain = new FilterChain();
        $chain->attach(new TestAsset\StripUpperCase())
            ->attach(new TestAsset\LowerCase(), 1001);
        $this->assertEquals($valueExpected, $chain->filter($value));

        $chain = new FilterChain();
        $chain->attach(new TestAsset\LowerCase(), 1001)
            ->attach(new TestAsset\StripUpperCase());
        $this->assertEquals($valueExpected, $chain->filter($value));

        $chain = new FilterChain();
        $chain->attach(new TestAsset\LowerCase(), 1001);
        $chainToMerge = new FilterChain();
        $chainToMerge->attach(new TestAsset\StripUpperCase());
        $chain->merge($chainToMerge);
        $this->assertEquals(2, $chain->count());
        $this->assertEquals($valueExpected, $chain->filter($value));

        $chain = new FilterChain();
        $chain->attach(new TestAsset\StripUpperCase());
        $chainToMerge = new FilterChain();
        $chainToMerge->attach(new TestAsset\LowerCase(), 1001);
        $chain->merge($chainToMerge);
        $this->assertEquals(2, $chain->count());
        $this->assertEquals($valueExpected, $chain->filter($value));
    }
}
