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
     * @return void
     */
    public function select(): void
    {
        $seed01 = [1, 2];
        $case01 = function (int $x) {
            return $x + 1;
        };
        $result01 = Linqinp::from($seed01)
            ->select($case01)
            ->toArray();
        $this->assertSame([2, 3], $result01);

        $seed02 = [10 => 'a', 11 => 'b'];
        $case02 = function (string $x, int $key) {
            return "The answer is {$x} and {$key}.";
        };
        $result02 = Linqinp::from($seed02)
            ->select($case02)
            ->toArray();
        $ex02 = [
            10 => "The answer is a and 10.",
            11 => "The answer is b and 11."
        ];
        $this->assertSame($ex02, $result02);

        $seed03 = ['key1' => 'value1', 'key2' => 'value2'];
        $case03 = function (string $x, string &$y) {
            $y = "new {$y}";
            return "The value is new {$x}. The key is {$y}.";
        };
        $result03 = Linqinp::from($seed03)
            ->select($case03)
            ->toArray();
        $ex03 = [
            'new key1' => "The value is new value1. The key is new key1.",
            'new key2' => "The value is new value2. The key is new key2."
        ];
        $this->assertSame($ex03, $result03);
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
