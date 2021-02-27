<?php

namespace LinqinpTest;

use ArrayIterator;
use EmptyIterator;
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
    private static string $caseReturnValueNull = 'return_value_null';

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
        $case00 = [];
        $expected00 = new ArrayIterator($case00);
        $set00 = [$case00, $expected00];

        $case01 = [1];
        $expected01 = new ArrayIterator($case01);
        $set01 = [$case01, $expected01];

        $case10 = new EmptyIterator();
        $ex10 = new EmptyIterator();
        $set10 = [$case10, $ex10];

        $func = function ($x) {
            yield $x;
        };

        $case11 = $func(2);
        $ex11 = $func(2);
        $set11 = [$case11, $ex11];

        return [
            'empty_array' => [$set00],
            'array' => [$set01],
            'empty_generator' => [$set10],
            'generator' => [$set11],
        ];
    }

    /**
     * @param array $seed
     * @param callable|null $func
     * @param mixed $exValue
     * @param string|null $exErrorClass
     * @param string|null $exErrorMessage
     * @return array[]
     */
    private function createCase(
        array $seed,
        ?callable $func,
        mixed $exValue,
        ?string $exErrorClass = null,
        ?string $exErrorMessage = null,
    ): array {
        return [[$seed, $func], [$exValue, $exErrorClass, $exErrorMessage]];
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
        list($exValue, $exErrorClass, $exErrorMessage) = $expected;

        if ($exErrorClass !== null) {
            $this->expectException($exErrorClass);
        }

        if ($exErrorMessage !== null) {
            $this->expectExceptionMessage($exErrorMessage);
        }

        $result = Linqinp::from($seed)
            ->select($func)
            ->toArray();
        $this->assertSame($exValue, $result);
    }

    /**
     * @return array
     */
    public function selectProvider(): array
    {
        $seed00 = [];
        $func00 = function (int $x) {
            return $x + 1;
        };
        $ex00 = [];
        $set00 = $this->createCase($seed00, $func00, $ex00);

        $seed01 = [1, 2];
        $func01 = function (int $x) {
            return $x + 1;
        };
        $ex01 = [2, 3];
        $set01 = $this->createCase($seed01, $func01, $ex01);;

        $seed02 = [10 => 'a', 11 => 'b'];
        $func02 = function (string $x, int $key) {
            return "The value is {$x}. The key is {$key}.";
        };
        $ex02 = [
            10 => "The value is a. The key is 10.",
            11 => "The value is b. The key is 11."
        ];
        $set02 = $this->createCase($seed02, $func02, $ex02);

        $seed03 = ['key1' => 'value1', 'key2' => 'value2'];
        $func03 = function (string $x, string &$y) {
            $y = "new {$y}";
            return "The value is new {$x}. The key is {$y}.";
        };
        $ex03 = [
            'new key1' => "The value is new value1. The key is new key1.",
            'new key2' => "The value is new value2. The key is new key2."
        ];
        $set03 = $this->createCase($seed03, $func03, $ex03);

        $seed04 = [1, 2, 3];
        $func04 = function (int $x, int &$y) {
            $y = $y * 0;
            return $x;
        };
        $exEC04 = InvalidArgumentException::class;
        $exEM04 = LinqinpLiteral::$errorKeyDuplicate;
        $set04 = $this->createCase($seed04, $func04, null, $exEC04, $exEM04);

        return [
            self::$caseEmpty => [$set00],
            self::$caseUseValue => [$set01],
            self::$caseUseKey => [$set02],
            self::$caseModifyKey => [$set03],
            self::$caseDuplicateKey => [$set04],
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
        list($exValue, $exErrorClass, $exErrorMessage) = $expected;

        if ($exErrorClass !== null) {
            $this->expectException($exErrorClass);
        }

        if ($exErrorMessage !== null) {
            $this->expectExceptionMessage($exErrorMessage);
        }

        $result = Linqinp::from($seed)
            ->where($func)
            ->toArray();
        $this->assertSame($exValue, $result);
    }

    /**
     * @return array
     */
    public function whereProvider(): array
    {
        $seed00 = [];
        $func00 = function (int $x) {
            return $x > 1;
        };
        $ex00 = [];
        $set00 = $this->createCase($seed00, $func00, $ex00);

        $seed01 = [1, 2, 3];
        $func01 = function (int $x) {
            return $x > 1;
        };
        $ex01 = [1 => 2, 2 => 3];
        $set01 = $this->createCase($seed01, $func01, $ex01);

        $seed02 = [10 => 'a', 11 => 'b', 12 => 'c'];
        $func02 = function (string $x, int $y) {
            return is_string($x) && $y > 10;
        };
        $ex02 = [
            11 => "b",
            12 => "c"
        ];
        $set02 = $this->createCase($seed02, $func02, $ex02);

        $seed03 = [100 => 10, 111 => 11];
        $func03 = function (int $x, int &$y) {
            $y = $y * $x;
            return true;
        };
        $ex03 = [1000 => 10, 1221 => 11];
        $set03 = $this->createCase($seed03, $func03, $ex03);

        $seed04 = [1, 2, 3];
        $func04 = function (int $x, int &$y) {
            $y = $x * $y * 0;
            return true;
        };
        $exEC04 = InvalidArgumentException::class;
        $exEM04 = LinqinpLiteral::$errorKeyDuplicate;
        $set04 = $this->createCase($seed04, $func04, null, $exEC04, $exEM04);

        $seed05 = [100 => 10, 111 => 11];
        $func05 = function () {
            return false;
        };
        $ex05 = [];
        $set05 = $this->createCase($seed05, $func05, $ex05);

        $seed06 = [1, 2, 3];
        $func06 = function (int $x) {
            return $x * 0 + 1;
        };
        $exEC06 = TypeError::class;
        $exEM06 = LinqinpLiteral::$errorCallableReturnTypeBool;
        $set06 = $this->createCase($seed06, $func06, null, $exEC06, $exEM06);

        return [
            self::$caseEmpty => [$set00],
            self::$caseUseValue => [$set01],
            self::$caseUseKey => [$set02],
            self::$caseModifyKey => [$set03],
            self::$caseDuplicateKey => [$set04],
            self::$caseValueNothing => [$set05],
            self::$caseReturnTypeIncorrect => [$set06],
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
        list($exValue, $exErrorClass, $exErrorMessage) = $expected;

        if ($exErrorClass !== null) {
            $this->expectException($exErrorClass);
        }

        if ($exErrorMessage !== null) {
            $this->expectExceptionMessage($exErrorMessage);
        }

        $result = Linqinp::from($seed)
            ->single($func);
        $this->assertSame($exValue, $result);
    }

    /**
     * @return array
     */
    public function singleProvider(): array
    {
        $seed00 = [];
        $func00 = function (int $x) {
            return $x > 1 && $x < 3;
        };
        $exEC = InvalidArgumentException::class;
        $exEM = LinqinpLiteral::$errorNoValue;
        $set00 = $this->createCase($seed00, $func00, null, $exEC, $exEM);

        $seed01 = [1, 2, 3];
        $func01 = function (int $x) {
            return $x > 1 && $x < 3;
        };
        $ex01 = 2;
        $set01 = $this->createCase($seed01, $func01, $ex01);

        $seed02 = [10 => 'a', 11 => 'b', 12 => 'c'];
        $func02 = function (string $x, int $y) {
            return is_string($x) && $y > 11;
        };
        $ex02 = 'c';
        $set02 = $this->createCase($seed02, $func02, $ex02);

        $seed03 = [null, 1, 2];
        $func03 = function (?int $x) {
            return $x === null;
        };
        $ex03 = null;
        $set03 = $this->createCase($seed03, $func03, $ex03);

        $seed04 = [1, 2, 3];
        $func04 = function (int $x, int $y) {
            return $x + $y;
        };
        $exEC04 = TypeError::class;
        $exEM04 = LinqinpLiteral::$errorCallableReturnTypeBool;
        $set04 = $this->createCase($seed04, $func04, null, $exEC04, $exEM04);

        $seed05 = [1, 2, 3];
        $func05 = function (int $x, int $y) {
            return !is_int($x) && !is_int($y);
        };
        $exEC05 = InvalidArgumentException::class;
        $exEM05 = LinqinpLiteral::$errorNoValue;
        $set05 = $this->createCase($seed05, $func05, null, $exEC05, $exEM05);

        $seed06 = [1, 2, 3];
        $func06 = function (int $x) {
            return is_int($x);
        };
        $exEC06 = InvalidArgumentException::class;
        $exEM06 = LinqinpLiteral::$errorTooMuchValues;
        $set06 = $this->createCase($seed06, $func06, null, $exEC06, $exEM06);

        return [
            self::$caseEmpty => [$set00],
            self::$caseUseValue => [$set01],
            self::$caseUseKey => [$set02],
            self::$caseReturnValueNull => [$set03],
            self::$caseReturnTypeIncorrect => [$set04],
            self::$caseValueNothing => [$set05],
            self::$caseValueTooMany => [$set06],
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
        list($exValue, $exErrorClass, $exErrorMessage) = $expected;

        if ($exErrorClass !== null) {
            $this->expectException($exErrorClass);
        }

        if ($exErrorMessage !== null) {
            $this->expectExceptionMessage($exErrorMessage);
        }

        $result = Linqinp::from($seed)
            ->singleOrDefault($func);
        $this->assertSame($exValue, $result);
    }

    /**
     * @return array
     */
    public function singleOrDefaultProvider(): array
    {
        $seed00 = [];
        $func00 = function (int $x) {
            return $x > 1 && $x < 3;
        };
        $ex00 = null;
        $set00 = $this->createCase($seed00, $func00, $ex00);

        $seed01 = [1, 2, 3];
        $func01 = function (int $x) {
            return $x > 1 && $x < 3;
        };
        $ex01 = 2;
        $set01 = $this->createCase($seed01, $func01, $ex01);

        $seed02 = [10 => 'a', 11 => 'b', 12 => 'c'];
        $func02 = function (string $x, int $y) {
            return is_string($x) && $y > 11;
        };
        $ex02 = 'c';
        $set02 = $this->createCase($seed02, $func02, $ex02);

        $seed03 = [null, 1, 2];
        $func03 = function (?int $x) {
            return $x === null;
        };
        $ex03 = null;
        $set03 = $this->createCase($seed03, $func03, $ex03);

        $seed04 = [1, 2, 3];
        $func04 = function (int $x, int $y) {
            return $x + $y;
        };
        $exEC04 = TypeError::class;
        $exEM04 = LinqinpLiteral::$errorCallableReturnTypeBool;
        $set04 = $this->createCase($seed04, $func04, null, $exEC04, $exEM04);

        $seed05 = [1, 2, 3];
        $func05 = function (int $x, int $y) {
            return !is_int($x) && !is_int($y);
        };
        $ex05 = null;
        $set05 = $this->createCase($seed05, $func05, $ex05);

        $seed06 = [1, 2, 3];
        $func06 = function (int $x) {
            return is_int($x);
        };
        $exEC06 = InvalidArgumentException::class;
        $exEM06 = LinqinpLiteral::$errorTooMuchValues;
        $set06 = $this->createCase($seed06, $func06, null, $exEC06, $exEM06);

        return [
            self::$caseEmpty => [$set00],
            self::$caseUseValue => [$set01],
            self::$caseUseKey => [$set02],
            self::$caseReturnValueNull => [$set03],
            self::$caseReturnTypeIncorrect => [$set04],
            self::$caseValueNothing => [$set05],
            self::$caseValueTooMany => [$set06],
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
        list($exValue, $exErrorClass, $exErrorMessage) = $expected;

        if ($exErrorClass !== null) {
            $this->expectException($exErrorClass);
        }

        if ($exErrorMessage !== null) {
            $this->expectExceptionMessage($exErrorMessage);
        }

        $result = Linqinp::from($seed)
            ->first($func);
        $this->assertSame($exValue, $result);
    }

    /**
     * @return array
     */
    public function firstProvider(): array
    {
        $seed00 = [];
        $func00 = function (int $x) {
            return $x > 1 && $x < 3;
        };
        $ex00 = null;
        $exEC00 = InvalidArgumentException::class;
        $exEM00 = LinqinpLiteral::$errorNoValue;
        $set00 = $this->createCase($seed00, $func00, null, $exEC00, $exEM00);

        $seed01 = [1, 2, 3, 4];
        $func01 = function (int $x) {
            return $x > 2;
        };
        $ex01 = 3;
        $set01 = $this->createCase($seed01, $func01, $ex01);

        $seed02 = [10 => 'a', 11 => 'b', 12 => 'c'];
        $func02 = function (string $x, int $y) {
            return is_string($x) && $y > 11;
        };
        $ex02 = 'c';
        $set02 = $this->createCase($seed02, $func02, $ex02);

        $seed03 = [null, 1, 2, null];
        $func03 = function (?int $x) {
            return $x === null;
        };
        $ex03 = null;
        $set03 = $this->createCase($seed03, $func03, $ex03);

        $seed04 = [0, 1, 2, null];
        $func04 = null;
        $ex04 = 0;
        $set04 = $this->createCase($seed04, $func04, $ex04);

        $seed05 = [1, 2, 3];
        $func05 = function (int $x, int $y) {
            return $x + $y;
        };
        $exEC05 = TypeError::class;
        $exEM05 = LinqinpLiteral::$errorCallableReturnTypeBool;
        $set05 = $this->createCase($seed05, $func05, null, $exEC05, $exEM05);

        $seed06 = [1, 2, 3];
        $func06 = function (int $x, int $y) {
            return !is_int($x) && !is_int($y);
        };
        $exEC06 = InvalidArgumentException::class;
        $exEM06 = LinqinpLiteral::$errorNoValue;

        $set06 = $this->createCase($seed06, $func06, null, $exEC06, $exEM06);

        return [
            self::$caseEmpty => [$set00],
            self::$caseUseValue => [$set01],
            self::$caseUseKey => [$set02],
            self::$caseReturnValueNull => [$set03],
            self::$caseReturnTypeIncorrect => [$set04],
            self::$caseValueNothing => [$set05],
            self::$caseValueTooMany => [$set06],
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
