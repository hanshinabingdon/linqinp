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
        $expected02 = $case02;
        $set02 = [$case02, $expected02];

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
}
