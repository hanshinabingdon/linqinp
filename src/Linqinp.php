<?php

namespace Linqinp;

use ArrayIterator;
use Generator;
use Iterator;

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
     * @param callable $func
     * @return Linqinp
     */
    public function select(callable $func): Linqinp
    {
        return new Linqinp($this->doSelect($func));
    }

    /**
     * @param callable $func
     * @return Generator
     */
    private function doSelect(callable $func): Generator
    {
        foreach ($this->target as $key => $value) {
            $tmp = $func($value, $key);
            yield $key => $tmp;
        }
    }

    /**
     * @param callable $func
     * @return Linqinp
     */
    public function where(callable $func): Linqinp
    {
        $this->target = $this->doWhere($func);
        return $this;
    }

    /**
     * @param callable $func
     * @return Generator
     */
    private function doWhere(callable $func): Generator
    {
        yield $func();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return iterator_to_array($this->target, true);
    }
}
