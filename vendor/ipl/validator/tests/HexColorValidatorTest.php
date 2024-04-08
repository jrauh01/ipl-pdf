<?php

namespace ipl\Tests\Validator;

use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Validator\HexColorValidator;

class HexColorValidatorTest extends TestCase
{
    public function testColorValidatorWithValidColor(): void
    {
        StaticTranslator::$instance = new NoopTranslator();

        $this->assertTrue((new HexColorValidator())->isValid('#09afAF'), '#09afAF is a valid color');
    }

    public function testColorValidatorWithInvalidColors(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
        $validator = new HexColorValidator();

        $this->assertFalse($validator->isValid(''), 'Empty value is not a valid color');
        $this->assertFalse($validator->isValid('09afAF'), '09afAF is not a valid color');
        $this->assertFalse($validator->isValid('#09afA'), '#09afA is not a valid color');
        $this->assertFalse($validator->isValid('#09afAFF'), '#09afAFF is not a valid color');
        $this->assertFalse($validator->isValid('#09afAG'), '#09afAG is not a valid color');
    }
}
