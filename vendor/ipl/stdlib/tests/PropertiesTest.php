<?php

namespace ipl\Tests\Stdlib;

use OutOfBoundsException;

class PropertiesTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPropertyThrowsOutOfBoundsExceptionIfUnset()
    {
        $subject = new TestClassUsingThePropertiesTrait();

        $this->expectException(OutOfBoundsException::class);

        $subject->foo;
    }

    public function testArrayAccessThrowsOutOfBoundsExceptionIfUnset()
    {
        $subject = new TestClassUsingThePropertiesTrait();

        $this->expectException(OutOfBoundsException::class);

        $subject['foo'];
    }

    public function testGetPropertyReturnsCorrectValueIfSet()
    {
        $subject = new TestClassUsingThePropertiesTrait();
        $subject->foo = 'bar';

        $this->assertSame('bar', $subject->foo);
    }

    public function testArrayAccessReturnsCorrectValueIfSet()
    {
        $subject = new TestClassUsingThePropertiesTrait();
        $subject['foo'] = 'bar';

        $this->assertSame('bar', $subject['foo']);
    }

    public function testIssetReturnsFalseForPropertyAccessIfUnset()
    {
        $subject = new TestClassUsingThePropertiesTrait();

        $this->assertFalse(isset($subject->foo));
    }

    public function testIssetReturnsFalseForArrayAccessIfUnset()
    {
        $subject = new TestClassUsingThePropertiesTrait();

        $this->assertFalse(isset($subject['foo']));
    }

    public function testIssetReturnsTrueForPropertyAccessIfSet()
    {
        $subject = new TestClassUsingThePropertiesTrait();
        $subject->foo = 'bar';

        $this->assertTrue(isset($subject->foo));
    }

    public function testIssetReturnsTrueForArrayAccessIfSet()
    {
        $subject = new TestClassUsingThePropertiesTrait();
        $subject->foo = 'bar';

        $this->assertTrue(isset($subject['foo']));
    }

    public function testUnsetForArrayAccess()
    {
        $subject = new TestClassUsingThePropertiesTrait();
        $subject['foo'] = 'bar';

        $this->assertSame('bar', $subject['foo']);

        unset($subject['foo']);

        $this->expectException(OutOfBoundsException::class);
        $subject['foo'];
    }

    public function testUnsetForPropertyAccess()
    {
        $subject = new TestClassUsingThePropertiesTrait();
        $subject->foo = 'bar';

        $this->assertSame('bar', $subject->foo);

        unset($subject->foo);

        $this->expectException(OutOfBoundsException::class);
        $subject->foo;
    }

    public function testPropertyIterationWorks()
    {
        $subject = new TestClassUsingThePropertiesTrait();
        $subject->foo = 'bar';
        $subject->bar = 'foo';

        $i = 0;
        foreach ($subject as $name => $value) {
            if ($i === 0) {
                $this->assertEquals($name, 'foo');
                $this->assertEquals($value, 'bar');
            } elseif ($i === 1) {
                $this->assertEquals($name, 'bar');
                $this->assertEquals($value, 'foo');
            } else {
                $this->fail('There are more properties iterable than defined');
            }

            $i++;
        }
    }

    public function testGetPropertiesReturnsEmptyArrayIfUnset()
    {
        $this->markTestSkipped('Properties::getProperties() not yet implemented');

        $subject = new TestClassUsingThePropertiesTrait();

        $this->assertSame([], $subject->getProperties());
    }

    public function testGetPropertiesReturnsCorrectValueIfSet()
    {
        $this->markTestSkipped('Properties::getProperties() not yet implemented');

        $subject = (new TestClassUsingThePropertiesTrait())
            ->setProperties(['foo' => 'bar', 'baz' => 'qux']);

        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $subject->getProperties());
    }
}
