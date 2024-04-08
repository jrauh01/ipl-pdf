<?php

namespace ipl\Tests\Validator;

use DateTime;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Validator\DateTimeValidator;

class DateTimeValidatorTest extends TestCase
{
    public function testDateTimeValidatorWithValidDateTime()
    {
        StaticTranslator::$instance = new NoopTranslator();
        $this->assertTrue((new DateTimeValidator())->isValid(new DateTime()), 'current date is a valid date');
    }

    public function testDateTimeValidatorWithFalseAsDateTimeValue()
    {
        StaticTranslator::$instance = new NoopTranslator();
        $validator = new DateTimeValidator();

        $this->assertFalse($validator->isValid(false), 'false is not a valid date');
    }

    public function testDateTimeValidatorWithStringAsDateTimeValue()
    {
        StaticTranslator::$instance = new NoopTranslator();
        $validator = new DateTimeValidator();

        $this->assertTrue($validator->isValid('2021-02-15T15:03:01'), '15th Feb is a valid date');
        $this->assertFalse($validator->isValid('2021-02-31T15:03:01'), '31st Feb is not a valid date');
        $this->assertFalse($validator->isValid('2021-02-03T26:03:01'), "26 o'clock is not a valid time");
        $this->assertFalse($validator->isValid(''), 'Empty value is not a valid date');
    }
}
