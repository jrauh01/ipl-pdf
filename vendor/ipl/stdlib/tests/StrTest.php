<?php

namespace ipl\Tests\Stdlib;

use ipl\Stdlib\Str;

class StrTest extends TestCase
{
    public function testCamelDoesNothingIfStringHasNoDelimitersAndIsLowerCase()
    {
        $this->assertSame('noop', Str::camel('noop'));
    }

    public function testCamelFromSnakeCaseString()
    {
        $this->assertSame('snakeCase', Str::camel('snake_case'));
    }

    public function testCamelFromKebabCaseString()
    {

        $this->assertSame('kebabCase', Str::camel('kebab-case'));
    }

    public function testCamelFromSpaceDelimitedString()
    {
        $this->assertSame('spaceDelimited', Str::camel('space delimited'));
    }

    public function testStartsWithReturnsTrueIfStringStartsWithTheSpecifiedSubstring()
    {
        $this->assertTrue(Str::startsWith('foobar', 'foo'));
    }

    public function testStartsWithReturnsFalseIfStringDoesNotStartWithTheSpecifiedSubstring()
    {
        $this->assertFalse(Str::startsWith('foobar', 'bar'));
    }

    public function testStartsWithReturnsTrueIfStringStartsWithTheSpecifiedSubstringAndCaseIsStrict()
    {
        $this->assertTrue(Str::startsWith('FOOBAR', 'FOO', true));
    }

    public function testStartsWithReturnsFalseIfStringDoesNotStartWithTheSpecifiedSubstringAndCaseIsStrict()
    {
        $this->assertFalse(Str::startsWith('FOOBAR', 'foo', true));
    }

    public function testSymmetricSplitReturnsArrayPaddedToTheSizeSpecifiedByLimitUsingNullAsValueByDefault()
    {
        $this->assertSame(['foo', 'bar', null, null], Str::symmetricSplit('foo,bar', ',', 4));
    }

    public function testSymmetricSplitReturnsArrayPaddedToTheSizeSpecifiedByLimitUsingCustomValue()
    {
        $this->assertSame(['foo', 'bar', false, false], Str::symmetricSplit('foo,bar', ',', 4, false));
    }

    public function testSymmetricSplitReturnsUnpaddedArrayIfTheSizeOfTheExplodedStringIsLessThanLimit()
    {
        $this->assertSame(['foo', 'bar,baz'], Str::symmetricSplit('foo,bar,baz', ',', 2));
    }

    public function testSymmetricSplitReturnsUnpaddedArrayIfTheSizeOfTheExplodedStringIsEqualToLimit()
    {
        $this->assertSame(['foo', 'bar'], Str::symmetricSplit('foo,bar', ',', 2));
    }

    public function testSymmetricSplitForSymmetricArrayDestructuring()
    {
        list($user, $password) = Str::symmetricSplit('root', ':', 2);

        $this->assertSame('root', $user);
        $this->assertNull($password);
    }

    public function testSymmetricSplitWithEmptySubjectStillReturnsAnArrayPaddedToTheDesiredSize()
    {
        $this->assertSame([null, null], Str::symmetricSplit(null, ',', 2));
    }

    public function testTrimSplitTrimsWhitespacesAndSplitsByCommaByDefault()
    {
        $this->assertSame(['foo', 'bar', 'baz'], Str::trimSplit(' foo ,bar  , baz  '));
    }

    public function testTrimSplitRespectsCustomDelimiter()
    {
        $this->assertSame(['foo', 'bar', 'baz'], Str::trimSplit(' foo .bar  . baz  ', '.'));
    }

    public function testTrimSplitRespectsLimit()
    {
        $this->assertSame(['foo', 'bar  , baz'], Str::trimSplit(' foo ,bar  , baz  ', ',', 2));
    }
}
