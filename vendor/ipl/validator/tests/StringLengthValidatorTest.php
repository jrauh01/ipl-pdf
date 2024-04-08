<?php

namespace ipl\Tests\Validator;

use InvalidArgumentException;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Validator\StringLengthValidator;
use LogicException;

class StringLengthValidatorTest extends TestCase
{
    public function testMinAndMaxOption(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
        $validator = new StringLengthValidator([
            'min'       => 5,
            'max'       => 7
        ]);

        $this->assertTrue($validator->isValid('Foobar'));
        $this->assertFalse($validator->isValid('Foobar Foobar'));
    }

    public function testOnlyMinOption(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
        $validator = new StringLengthValidator(['min' => 5]);

        $this->assertTrue($validator->isValid('Foobar'));
        $this->assertFalse($validator->isValid('Foo'));
    }

    public function testOnlyMaxOption(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
        $validator = new StringLengthValidator(['max' => 7]);

        $this->assertTrue($validator->isValid('Foobar'));
        $this->assertFalse($validator->isValid('Foobar Foobar'));
    }

    public function testMinGreaterThanMaxOptionThrowsAnException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The min must be less than or equal to the max length, but min: 10 and max: 5 given.'
        );

        (new StringLengthValidator())
            ->setMax(5)
            ->setMin(10);
    }

    public function testInvalidEncodingOptionThrowsAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Given encoding "foo" is not supported on this OS!'
        );

        (new StringLengthValidator())->setEncoding('foo');
    }
}
