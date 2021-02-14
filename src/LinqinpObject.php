<?php

namespace Linqinp;

/**
 * Class LinqinpObject
 * @package Linqinp
 */
class LinqinpObject
{
    /**
     * LinqinpObject constructor.
     * @param iterable $target
     */
    public function __construct(private iterable $target)
    {
    }

    public function test()
    {
        $hoge = [];
        Linqinp::from($hoge)
            ->select(
                function ($x) {
                    return $x;
                }
            )
            ->where(
                function ($x) {
                    return true;
                }
            )->toArray();
    }
}
