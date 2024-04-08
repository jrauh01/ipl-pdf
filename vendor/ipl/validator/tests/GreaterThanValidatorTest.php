<?php

namespace ipl\Tests\Validator;

use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Validator\GreaterThanValidator;

class GreaterThanValidatorTest extends TestCase
{
    public function testValidation(): void
    {
        StaticTranslator::$instance = new NoopTranslator();

        $validator = new GreaterThanValidator(['min' => 5]);

        $this->assertTrue($validator->isValid(7));
        $this->assertTrue($validator->isValid(70.99));
        $this->assertTrue($validator->isValid(5.1));


        $this->assertFalse($validator->isValid(4.9999));
        $this->assertFalse($validator->isValid(0.5000));
        $this->assertFalse($validator->isValid(-500));
    }
}
