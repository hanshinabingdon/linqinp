<?php

namespace LinqinpTest;

use ArrayIterator;
use Generator;
use Linqinp\Linqinp;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Iterator;

/**
 * Class LinqinpTest
 * @package LinqinpTest
 */
class LinqinpTest extends TestCase
{
    /**
     * @test
     * @dataProvider fromProvider
     * @param array $set
     * @return void
     */
    public function from(array $set): void
    {
        /** @var Iterator $expected */
        list($case, $expected) = $set;

        $target = Linqinp::from($case);

        $ref = new ReflectionClass(Linqinp::class);
        $prop = $ref->getProperty('target');
        $prop->setAccessible(true);

        foreach ($prop->getValue($target) as $key => $value) {
            $this->assertSame($expected->key(), $key);
            $this->assertSame($expected->current(), $value);
        }
    }

    /**
     * @return array
     */
    public function fromProvider(): array
    {
        $case01 = [1];
        $expected01 = new ArrayIterator($case01);
        $set01 = [$case01, $expected01];

        $case02 = $this->createGenerator(2);
        $set02 = [$case02, $case02];

        return [
            [$set01],
            [$set02]
        ];
    }

    /**
     * @test
     * @param array $set
     * @return void
     * @dataProvider selectProvider
     */
    public function select(array $set): void
    {
        list($case, $expected) = $set;
        list($seed, $func) = $case;

        $result = Linqinp::from($seed)
            ->select($func)
            ->toArray();
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function selectProvider(): array
    {
        $seed01 = [1, 2];
        $func01 = function (int $x) {
            return $x + 1;
        };
        $ex01 = [2, 3];
        $set01 = [[$seed01, $func01], $ex01];

        $seed02 = [10 => 'a', 11 => 'b'];
        $func02 = function (string $x, int $key) {
            return "The value is {$x}. The key is {$key}.";
        };
        $ex02 = [
            10 => "The value is a. The key is 10.",
            11 => "The value is b. The key is 11."
        ];
        $set02 = [[$seed02, $func02], $ex02];

        $seed03 = ['key1' => 'value1', 'key2' => 'value2'];
        $func03 = function (string $x, string &$y) {
            $y = "new {$y}";
            return "The value is new {$x}. The key is {$y}.";
        };
        $ex03 = [
            'new key1' => "The value is new value1. The key is new key1.",
            'new key2' => "The value is new value2. The key is new key2."
        ];
        $set03 = [[$seed03, $func03], $ex03];

        return [
            [$set01],
            [$set02],
            [$set03],
        ];
    }

    /**
     * @param mixed $value
     * @return Generator
     */
    public function createGenerator(mixed $value): Generator
    {
        yield $value;
    }
}
