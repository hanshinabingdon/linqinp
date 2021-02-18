<?php

namespace LinqinpTest;

use ArrayIterator;
use Generator;
use InvalidArgumentException;
use Linqinp\Linqinp;
use Linqinp\LinqinpLiteral;
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
     * @param mixed $value
     * @return Generator
     */
    public function createGenerator(mixed $value): Generator
    {
        yield $value;
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
     * @test
     * @dataProvider selectErrorProvider
     * @param array $set
     * @return void
     */
    public function selectError(array $set): void
    {
        list($case, $ex) = $set;

        list($exErrorClass, $exErrorMessage) = $ex;

        $this->expectException($exErrorClass);
        $this->expectExceptionMessage($exErrorMessage);

        list($seed, $func) = $case;

        Linqinp::from($seed)
            ->select($func)
            ->toArray();
    }

    /**
     * @return array
     */
    public function selectErrorProvider(): array
    {
        $seed01 = [1, 2, 3];
        $func01 = function (int $x, int &$y) {
            $y = $y * 0;
            return $x;
        };

        $exErrorClass01 = InvalidArgumentException::class;
        $exErrorMessage01 = LinqinpLiteral::$errorKeyDuplicate;

        $set01 = [
            [$seed01, $func01],
            [$exErrorClass01, $exErrorMessage01]
        ];

        return [
            [$set01]
        ];
    }

    /**
     * @test
     * @param array $set
     * @return void
     * @dataProvider whereProvider
     */
    public function where(array $set): void
    {
        list($case, $expected) = $set;
        list($seed, $func) = $case;

        $result = Linqinp::from($seed)
            ->where($func)
            ->toArray();
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function whereProvider(): array
    {
        $seed01 = [1, 2, 3];
        $func01 = function (int $x) {
            return $x > 1;
        };
        $ex01 = [1 => 2, 2 => 3];
        $set01 = [[$seed01, $func01], $ex01];

        $seed02 = [10 => 'a', 11 => 'b', 12 => 'c'];
        $func02 = function (string $x, int $y) {
            return $y > 10;
        };
        $ex02 = [
            11 => "b",
            12 => "c"
        ];
        $set02 = [[$seed02, $func02], $ex02];

        $seed03 = [100 => 10, 111 => 11];
        $func03 = function (int $x, int &$y) {
            $y = $y * $x;
            return true;
        };
        $ex03 = [1000 => 10, 1221 => 11];
        $set03 = [[$seed03, $func03], $ex03];

        $seed04 = [100 => 10, 111 => 11];
        $func04 = function () {
            return false;
        };
        $ex04 = [];
        $set04 = [[$seed04, $func04], $ex04];

        return [
            [$set01],
            [$set02],
            [$set03],
            [$set04],
        ];
    }

}
