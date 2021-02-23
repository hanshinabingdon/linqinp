<?php

namespace LinqinpTest;

use ArrayIterator;
use EmptyIterator;
use Generator;
use InvalidArgumentException;
use Linqinp\Linqinp;
use Linqinp\LinqinpLiteral;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Iterator;
use TypeError;

/**
 * Class LinqinpTest
 * @package LinqinpTest
 */
class LinqinpTest extends TestCase
{
    #region case list
    /** @var string */
    private static string $caseEmpty = 'empty';

    /** @var string */
    private static string $caseUseValue = 'use_value';

    /** @var string */
    private static string $caseUseKey = 'use_key';

    /** @var string */
    private static string $caseModifyKey = 'modify_key';

    /** @var string */
    private static string $caseDuplicateKey = 'duplicateKey';

    /** @var string */
    private static string $caseReturnTypeIncorrect = 'return_type_incorrect';

    /** @var string */
    private static string $caseValueNothing = 'value_nothing';

    /** @var string */
    private static string $caseValueTooMany = 'value_too_many';
    #endregion

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

        $targetValue = iterator_to_array($prop->getValue($target));
        if (empty($targetValue)) {
            $this->expectNotToPerformAssertions();
            return;
        }

        foreach ($targetValue as $key => $value) {
            $this->assertSame($expected->key(), $key);
            $this->assertSame($expected->current(), $value);
        }
    }

    /**
     * @return array
     */
    public function fromProvider(): array
    {
        $case01 = [];
        $expected01 = new ArrayIterator($case01);
        $set01 = [$case01, $expected01];

        $case02 = [1];
        $expected02 = new ArrayIterator($case02);
        $set02 = [$case02, $expected02];

        $case03 = new EmptyIterator();
        $ex03 = new EmptyIterator();
        $set03 = [$case03, $ex03];

        $case04 = $this->createGenerator(2);
        $ex04 = $this->createGenerator(2);
        $set04 = [$case04, $ex04];

        return [
            'empty_array' => [$set01],
            'array' => [$set02],
            'empty_generator' => [$set03],
            'generator' => [$set04],
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
            return is_string($x) && $y > 10;
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

    /**
     * @test
     * @dataProvider whereErrorProvider
     * @param array $set
     * @return void
     */
    public function whereError(array $set): void
    {
        list($case, $ex) = $set;

        list($exErrorClass, $exErrorMessage) = $ex;

        $this->expectException($exErrorClass);
        $this->expectExceptionMessage($exErrorMessage);

        list($seed, $func) = $case;

        Linqinp::from($seed)
            ->where($func)
            ->toArray();
    }

    /**
     * @return array
     */
    public function whereErrorProvider(): array
    {
        $seed01 = [1, 2, 3];
        $func01 = function (int $x, int &$y) {
            $y = $x * $y * 0;
            return true;
        };
        $exErrorClass01 = InvalidArgumentException::class;
        $exErrorMessage01 = LinqinpLiteral::$errorKeyDuplicate;
        $set01 = [
            [$seed01, $func01],
            [$exErrorClass01, $exErrorMessage01]
        ];

        $seed02 = [1, 2, 3];
        $func02 = function (int $x) {
            return $x * 0 + 1;
        };
        $exErrorClass02 = TypeError::class;
        $exErrorMessage02 = LinqinpLiteral::$errorCallableReturnTypeBool;

        $set02 = [
            [$seed02, $func02],
            [$exErrorClass02, $exErrorMessage02]
        ];


        return [
            [$set01],
            [$set02]
        ];
    }

    /**
     * @test
     * @param array $set
     * @return void
     * @dataProvider singleProvider
     */
    public function single(array $set): void
    {
        list($case, $expected) = $set;
        list($seed, $func) = $case;

        $result = Linqinp::from($seed)
            ->single($func);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function singleProvider(): array
    {
        $seed01 = [1, 2, 3];
        $func01 = function (int $x) {
            return $x > 1 && $x < 3;
        };
        $ex01 = 2;
        $set01 = [[$seed01, $func01], $ex01];

        $seed02 = [10 => 'a', 11 => 'b', 12 => 'c'];
        $func02 = function (string $x, int $y) {
            return is_string($x) && $y > 11;
        };
        $ex02 = 'c';
        $set02 = [[$seed02, $func02], $ex02];

        $seed03 = [null, 1, 2];
        $func03 = function (?int $x) {
            return $x === null;
        };
        $ex03 = null;
        $set03 = [[$seed03, $func03], $ex03];

        return [
            [$set01],
            [$set02],
            [$set03],
        ];
    }

    /**
     * @test
     * @dataProvider singleErrorProvider
     * @param array $set
     * @return void
     */
    public function singleError(array $set): void
    {
        list($case, $ex) = $set;

        list($exErrorClass, $exErrorMessage) = $ex;

        $this->expectException($exErrorClass);
        $this->expectExceptionMessage($exErrorMessage);

        list($seed, $func) = $case;

        Linqinp::from($seed)
            ->single($func);
    }

    /**
     * @return array
     */
    public function singleErrorProvider(): array
    {
        $seed01 = [1, 2, 3];
        $func01 = function (int $x, int $y) {
            return $x + $y;
        };
        $exErrorClass01 = TypeError::class;
        $exErrorMessage01 = LinqinpLiteral::$errorCallableReturnTypeBool;
        $set01 = [
            [$seed01, $func01],
            [$exErrorClass01, $exErrorMessage01]
        ];

        $seed02 = [1, 2, 3];
        $func02 = function (int $x) {
            return is_int($x);
        };
        $exErrorClass02 = InvalidArgumentException::class;
        $exErrorMessage02 = LinqinpLiteral::$errorTooMuchValues;

        $set02 = [
            [$seed02, $func02],
            [$exErrorClass02, $exErrorMessage02]
        ];

        $seed03 = [1, 2, 3];
        $func03 = function (int $x, int $y) {
            return !is_int($x) && !is_int($y);
        };
        $exErrorClass03 = InvalidArgumentException::class;
        $exErrorMessage03 = LinqinpLiteral::$errorNoValue;

        $set03 = [
            [$seed03, $func03],
            [$exErrorClass03, $exErrorMessage03]
        ];

        return [
            [$set01],
            [$set02],
            [$set03],
        ];
    }

    /**
     * @test
     * @param array $set
     * @return void
     * @dataProvider singleOrDefaultProvider
     */
    public function singleOrDefault(array $set): void
    {
        list($case, $expected) = $set;
        list($seed, $func) = $case;

        $result = Linqinp::from($seed)
            ->singleOrDefault($func);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function singleOrDefaultProvider(): array
    {
        $seed01 = [1, 2, 3];
        $func01 = function (int $x) {
            return $x > 1 && $x < 3;
        };
        $ex01 = 2;
        $set01 = [[$seed01, $func01], $ex01];

        $seed02 = [10 => 'a', 11 => 'b', 12 => 'c'];
        $func02 = function (string $x, int $y) {
            return is_string($x) && $y > 11;
        };
        $ex02 = 'c';
        $set02 = [[$seed02, $func02], $ex02];

        $seed03 = [null, 1, 2];
        $func03 = function (?int $x) {
            return $x === null;
        };
        $ex03 = null;
        $set03 = [[$seed03, $func03], $ex03];

        $seed04 = [1, 2, 3];
        $func04 = function (int $x, int $y) {
            return !is_int($x) && !is_int($y);
        };
        $ex04 = null;

        $set04 = [[$seed04, $func04], $ex04];

        return [
            [$set01],
            [$set02],
            [$set03],
            [$set04],
        ];
    }

    /**
     * @test
     * @dataProvider singleOrDefaultErrorProvider
     * @param array $set
     * @return void
     */
    public function singleOrDefaultError(array $set): void
    {
        list($case, $ex) = $set;

        list($exErrorClass, $exErrorMessage) = $ex;

        $this->expectException($exErrorClass);
        $this->expectExceptionMessage($exErrorMessage);

        list($seed, $func) = $case;

        Linqinp::from($seed)
            ->singleOrDefault($func);
    }

    /**
     * @return array
     */
    public function singleOrDefaultErrorProvider(): array
    {
        $seed01 = [1, 2, 3];
        $func01 = function (int $x, int $y) {
            return $x + $y;
        };
        $exErrorClass01 = TypeError::class;
        $exErrorMessage01 = LinqinpLiteral::$errorCallableReturnTypeBool;
        $set01 = [
            [$seed01, $func01],
            [$exErrorClass01, $exErrorMessage01]
        ];

        $seed02 = [1, 2, 3];
        $func02 = function (int $x) {
            return is_int($x);
        };
        $exErrorClass02 = InvalidArgumentException::class;
        $exErrorMessage02 = LinqinpLiteral::$errorTooMuchValues;

        $set02 = [
            [$seed02, $func02],
            [$exErrorClass02, $exErrorMessage02]
        ];

        return [
            [$set01],
            [$set02],
        ];
    }

    /**
     * @test
     * @param array $set
     * @return void
     * @dataProvider firstProvider
     */
    public function first(array $set): void
    {
        list($case, $expected) = $set;
        list($seed, $func) = $case;

        $result = Linqinp::from($seed)
            ->first($func);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function firstProvider(): array
    {
        $seed01 = [1, 2, 3, 4];
        $func01 = function (int $x) {
            return $x > 2;
        };
        $ex01 = 3;
        $set01 = [[$seed01, $func01], $ex01];

        $seed02 = [10 => 'a', 11 => 'b', 12 => 'c'];
        $func02 = function (string $x, int $y) {
            return is_string($x) && $y > 11;
        };
        $ex02 = 'c';
        $set02 = [[$seed02, $func02], $ex02];

        $seed03 = [null, 1, 2, null];
        $func03 = function (?int $x) {
            return $x === null;
        };
        $ex03 = null;
        $set03 = [[$seed03, $func03], $ex03];

        $seed04 = [0, 1, 2, null];
        $func04 = null;
        $ex04 = 0;
        $set04 = [[$seed04, $func04], $ex04];

        return [
            [$set01],
            [$set02],
            [$set03],
            [$set04],
        ];
    }

    /**
     * @test
     * @dataProvider firstErrorProvider
     * @param array $set
     * @return void
     */
    public function firstError(array $set): void
    {
        list($case, $ex) = $set;

        list($exErrorClass, $exErrorMessage) = $ex;

        $this->expectException($exErrorClass);
        $this->expectExceptionMessage($exErrorMessage);

        list($seed, $func) = $case;

        Linqinp::from($seed)
            ->first($func);
    }

    /**
     * @return array
     */
    public function firstErrorProvider(): array
    {
        $seed01 = [1, 2, 3];
        $func01 = function (int $x, int $y) {
            return $x + $y;
        };
        $exErrorClass01 = TypeError::class;
        $exErrorMessage01 = LinqinpLiteral::$errorCallableReturnTypeBool;
        $set01 = [
            [$seed01, $func01],
            [$exErrorClass01, $exErrorMessage01]
        ];

        $seed02 = [1, 2, 3];
        $func02 = function (int $x, int $y) {
            return !is_int($x) && !is_int($y);
        };
        $exErrorClass02 = InvalidArgumentException::class;
        $exErrorMessage02 = LinqinpLiteral::$errorNoValue;

        $set02 = [
            [$seed02, $func02],
            [$exErrorClass02, $exErrorMessage02]
        ];

        return [
            [$set01],
            [$set02],
        ];
    }

    /**
     * @test
     * @param array $set
     * @return void
     * @dataProvider firstOrDefaultProvider
     */
    public function firstOrDefault(array $set): void
    {
        list($case, $expected) = $set;
        list($seed, $func) = $case;

        $result = Linqinp::from($seed)
            ->firstOrDefault($func);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function firstOrDefaultProvider(): array
    {
        $seed01 = [1, 2, 3, 4];
        $func01 = function (int $x) {
            return $x > 2;
        };
        $ex01 = 3;
        $set01 = [[$seed01, $func01], $ex01];

        $seed02 = [10 => 'a', 11 => 'b', 12 => 'c'];
        $func02 = function (string $x, int $y) {
            return is_string($x) && $y > 11;
        };
        $ex02 = 'c';
        $set02 = [[$seed02, $func02], $ex02];

        $seed03 = [null, 1, 2, null];
        $func03 = function (?int $x) {
            return $x === null;
        };
        $ex03 = null;
        $set03 = [[$seed03, $func03], $ex03];

        $seed04 = [1, 2, 3];
        $func04 = function (int $x, int $y) {
            return !is_int($x) && !is_int($y);
        };
        $ex04 = null;
        $set04 = [[$seed04, $func04], $ex04];

        $seed05 = [];
        $func05 = null;
        $ex05 = null;
        $set05 = [[$seed05, $func05], $ex05];

        return [
            [$set01],
            [$set02],
            [$set03],
            [$set04],
            [$set05],
        ];
    }

    /**
     * @test
     * @dataProvider firstOrDefaultErrorProvider
     * @param array $set
     * @return void
     */
    public function firstOrDefaultError(array $set): void
    {
        list($case, $ex) = $set;

        list($exErrorClass, $exErrorMessage) = $ex;

        $this->expectException($exErrorClass);
        $this->expectExceptionMessage($exErrorMessage);

        list($seed, $func) = $case;

        Linqinp::from($seed)
            ->firstOrDefault($func);
    }

    /**
     * @return array
     */
    public function firstOrDefaultErrorProvider(): array
    {
        $seed01 = [1, 2, 3];
        $func01 = function (int $x, int $y) {
            return $x + $y;
        };
        $exErrorClass01 = TypeError::class;
        $exErrorMessage01 = LinqinpLiteral::$errorCallableReturnTypeBool;
        $set01 = [
            [$seed01, $func01],
            [$exErrorClass01, $exErrorMessage01]
        ];

        return [
            [$set01],
        ];
    }
}
