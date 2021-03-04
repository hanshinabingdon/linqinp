<?php

namespace Linqinp;

use ArrayIterator;
use Generator;
use InvalidArgumentException;
use Iterator;
use TypeError;

/**
 * Class Linqinp
 * @package Linqinp
 */
class Linqinp
{
    /**
     * Linqinp constructor.
     * @param Iterator $target
     */
    private function __construct(private Iterator $target)
    {
    }

    /**
     * @param Iterator|array $target
     * @return Linqinp
     */
    public static function from(Iterator|array $target): Linqinp
    {
        if (is_array($target)) {
            return new Linqinp(new ArrayIterator($target));
        }

        return new Linqinp($target);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return iterator_to_array($this->target, true);
    }

    /**
     * @param callable $func
     * @return Linqinp
     */
    public function select(callable $func): Linqinp
    {
        return new Linqinp($this->doSelect($this->target, $func));
    }

    /**
     * @param Iterator $target
     * @param callable $func
     * @return Generator
     */
    private function doSelect(Iterator $target, callable $func): Generator
    {
        $keys = [];
        foreach ($target as $key => $value) {
            $tmp = $func($value, $key);

            if (in_array($key, $keys, true)) {
                throw new InvalidArgumentException(LinqinpLiteral::$errorKeyDuplicate);
            }

            $keys[] = $key;
            yield $key => $tmp;
        }
    }

    /**
     * @param callable $func
     * @return Linqinp
     */
    public function where(callable $func): Linqinp
    {
        return new Linqinp($this->doWhere($this->target, $func));
    }

    /**
     * @param Iterator $target
     * @param callable $func
     * @return Generator
     */
    private function doWhere(Iterator $target, callable $func): Generator
    {
        $keys = [];
        foreach ($target as $key => $value) {
            $tmp = $func($value, $key);

            if (!is_bool($tmp)) {
                throw new TypeError(LinqinpLiteral::$errorCallableReturnTypeBool);
            }

            if (!$tmp) {
                continue;
            }

            if (in_array($key, $keys, true)) {
                throw new InvalidArgumentException(LinqinpLiteral::$errorKeyDuplicate);
            }

            $keys[] = $key;
            yield $key => $value;
        }
    }

    /**
     * @param callable $func
     * @return mixed
     */
    public function single(callable $func): mixed
    {
        return $this->doSingle($func, false);
    }

    /**
     * @param callable $func
     * @return mixed
     */
    public function singleOrDefault(callable $func): mixed
    {
        return $this->doSingle($func, true);
    }

    /**
     * @param callable $func
     * @param bool $allowEmpty
     * @return mixed
     */
    private function doSingle(callable $func, bool $allowEmpty): mixed
    {
        $targets = [];
        foreach ($this->target as $key => $value) {
            $tmp = $func($value, $key);

            if (!is_bool($tmp)) {
                throw new TypeError(LinqinpLiteral::$errorCallableReturnTypeBool);
            }

            if (!$tmp) {
                continue;
            }

            $targets[] = $value;
        }

        if (count($targets) > 1) {
            throw new InvalidArgumentException(LinqinpLiteral::$errorTooMuchValues);
        }

        if (empty($targets)) {
            if ($allowEmpty) {
                return null;
            }

            throw new InvalidArgumentException(LinqinpLiteral::$errorNoValue);
        }

        return array_shift($targets);
    }

    /**
     * @param callable|null $func
     * @return mixed
     */
    public function first(?callable $func = null): mixed
    {
        return $this->doFirst($func, false);
    }

    /**
     * @param callable|null $func
     * @return mixed
     */
    public function firstOrDefault(?callable $func = null): mixed
    {
        return $this->doFirst($func, true);
    }

    /**
     * @param callable|null $func
     * @param bool $allowEmpty
     * @return mixed
     */
    private function doFirst(?callable $func, bool $allowEmpty): mixed
    {
        if ($func === null) {
            $tmp = $this->toArray();
            if (empty($tmp)) {
                if ($allowEmpty) {
                    return null;
                }

                throw new InvalidArgumentException(LinqinpLiteral::$errorNoValue);
            }

            return array_shift($tmp);
        }

        foreach ($this->target as $key => $value) {
            $tmp = $func($value, $key);

            if (!is_bool($tmp)) {
                throw new TypeError(LinqinpLiteral::$errorCallableReturnTypeBool);
            }

            if (!$tmp) {
                continue;
            }

            return $value;
        }

        if ($allowEmpty) {
            return null;
        }

        throw new InvalidArgumentException(LinqinpLiteral::$errorNoValue);
    }

    /**
     * @param callable|null $func
     * @return mixed
     */
    public function last(?callable $func = null): mixed
    {
        return $this->doLast($func, false);
    }

    /**
     * @param callable|null $func
     * @return mixed
     */
    public function lastOrDefault(?callable $func = null): mixed
    {
        return $this->doLast($func, true);
    }

    /**
     * @param callable|null $func
     * @param bool $allowEmpty
     * @return mixed
     */
    private function doLast(?callable $func, bool $allowEmpty): mixed
    {
        $tmp = array_reverse($this->toArray(), true);
        if ($func === null) {
            if (empty($tmp)) {
                if ($allowEmpty) {
                    return null;
                }

                throw new InvalidArgumentException(LinqinpLiteral::$errorNoValue);
            }

            return array_shift($tmp);
        }

        foreach ($tmp as $key => $value) {
            $funcValue = $func($value, $key);

            if (!is_bool($funcValue)) {
                throw new TypeError(LinqinpLiteral::$errorCallableReturnTypeBool);
            }

            if (!$funcValue) {
                continue;
            }

            return $value;
        }

        if ($allowEmpty) {
            return null;
        }

        throw new InvalidArgumentException(LinqinpLiteral::$errorNoValue);
    }

    /**
     * @param callable|null $func
     * @return int
     */
    public function count(?callable $func = null): int
    {
        if ($func === null) {
            return iterator_count($this->target);
        }

        $count = 0;
        foreach ($this->target as $key => $value) {
            $tmp = $func($value, $key);

            if (!is_bool($tmp)) {
                throw new TypeError(LinqinpLiteral::$errorCallableReturnTypeBool);
            }

            if (!$tmp) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    /**
     * @param callable|null $func
     * @return bool
     */
    public function any(?callable $func = null): bool
    {
        if ($func === null) {
            return iterator_count($this->target) > 0;
        }

        foreach ($this->target as $key => $value) {
            $tmp = $func($value, $key);

            if (!is_bool($tmp)) {
                throw new TypeError(LinqinpLiteral::$errorCallableReturnTypeBool);
            }

            if (!$tmp) {
                continue;
            }

            return true;
        }

        return false;
    }
}
