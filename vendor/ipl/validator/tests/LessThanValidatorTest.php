<?php

namespace ipl\Tests\Validator;

use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Validator\LessThanValidator;

class LessThanValidatorTest extends TestCase
{
    public function testValidation(): void
    {
        StaticTranslator::$instance = new NoopTranslator();

        $validator = new LessThanValidator(['max' => 5]);

        $this->assertTrue($validator->isValid(4.9999));
        $this->assertTrue($validator->isValid(0.5000));
        $this->assertTrue($validator->isValid(-500));

        $this->assertFalse($validator->isValid(7));
        $this->assertFalse($validator->isValid(70.99));
        $this->assertFalse($validator->isValid(5.1));
    }
}
