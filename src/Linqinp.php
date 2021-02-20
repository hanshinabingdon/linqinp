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
    }
}
