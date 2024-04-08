<?php

namespace ipl\Tests\Stdlib;

use ArrayIterator;
use ipl\Stdlib\Seq;

class SeqTest extends TestCase
{
    public function testFindWithArrays()
    {
        $this->assertEquals(
            ['oof', 'BAR'],
            Seq::find(['foo' => 'bar', 'oof' => 'BAR'], 'BAR')
        );
        $this->assertEquals(
            ['foo', 'bar'],
            Seq::find(['foo' => 'bar', 'oof' => 'BAR'], 'BAR', false)
        );
    }

    public function testFindWithGenerators()
    {
        $generatorCreator = function () {
            yield 'foo' => 'bar';
            yield 'oof' => 'BAR';
        };

        $this->assertEquals(
            ['oof', 'BAR'],
            Seq::find($generatorCreator(), 'BAR')
        );
        $this->assertEquals(
            ['foo', 'bar'],
            Seq::find($generatorCreator(), 'BAR', false)
        );
    }

    public function testFindWithIterators()
    {
        $this->assertEquals(
            ['oof', 'BAR'],
            Seq::find(new ArrayIterator(['foo' => 'bar', 'oof' => 'BAR']), 'BAR')
        );
        $this->assertEquals(
            ['foo', 'bar'],
            Seq::find(new ArrayIterator(['foo' => 'bar', 'oof' => 'BAR']), 'BAR', false)
        );
    }
    public function testFindValueWithArrays()
    {
        $this->assertEquals(
            'BAR',
            Seq::findValue(['foo' => 'bar', 'FOO' => 'BAR'], 'FOO')
        );
        $this->assertEquals(
            'bar',
            Seq::findValue(['foo' => 'bar', 'FOO' => 'BAR'], 'FOO', false)
        );
    }

    public function testFindValueWithGenerators()
    {
        $generatorCreator = function () {
            yield 'foo' => 'bar';
            yield 'FOO' => 'BAR';
        };

        $this->assertEquals(
            'BAR',
            Seq::findValue($generatorCreator(), 'FOO')
        );
        $this->assertEquals(
            'bar',
            Seq::findValue($generatorCreator(), 'FOO', false)
        );
    }

    public function testFindValueWithIterators()
    {
        $this->assertEquals(
            'BAR',
            Seq::findValue(new ArrayIterator(['foo' => 'bar', 'FOO' => 'BAR']), 'FOO')
        );
        $this->assertEquals(
            'bar',
            Seq::findValue(new ArrayIterator(['foo' => 'bar', 'FOO' => 'BAR']), 'FOO', false)
        );
    }

    public function testFindWithCallback()
    {
        $this->assertEquals(
            [1, 'foo'],
            Seq::find(
                ['bar', 'foo'],
                function ($value) {
                    return $value !== 'bar';
                },
                false // Should have no effect
            )
        );
        $this->assertEquals(
            'foo',
            Seq::findValue(
                ['bar', 'foo'],
                function ($value) {
                    return $value !== 0;
                },
                false // Should have no effect
            )
        );
    }

    public function testFindWithFunctionName()
    {
        $this->assertEquals(
            [1, 'sleep'],
            Seq::find(
                ['awake', 'sleep'],
                'sleep'
            )
        );
        $this->assertEquals(
            'sleep',
            Seq::findValue(
                ['awake', 'sleep' => 'sleep'],
                'sleep'
            )
        );
    }
}
