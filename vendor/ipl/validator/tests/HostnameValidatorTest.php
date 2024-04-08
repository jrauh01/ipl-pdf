<?php

namespace ipl\Tests\Validator;

use ipl\I18n\NoopTranslator;
use ipl\Validator\HostnameValidator;
use ipl\I18n\StaticTranslator;

class HostnameValidatorTest extends TestCase
{
    /** @var HostnameValidator */
    protected $validator;

    public function setUp(): void
    {
        $this->validator = new HostnameValidator();
        StaticTranslator::$instance = new NoopTranslator();
    }

    public function validate($expected, $value)
    {
        $this->assertSame(
            $expected,
            $this->validator->isValid($value),
            implode("\n", $this->validator->getMessages())
        );
    }

    public function testValidCases()
    {
        $this->validate(true, 'localhost');
        $this->validate(true, 'localhost.localdomain');
        $this->validate(true, 'example.com');
        $this->validate(true, 'www.example.com');
        $this->validate(true, 'ex.ample.com');
    }

    public function testCommonTypos()
    {
        $this->validate(false, 'local host');
        $this->validate(false, 'example,com');
        $this->validate(false, 'exam_ple.com');
    }

    public function testDashes()
    {
        $this->validate(true, 'domain.com');
        $this->validate(true, 'doma-in.com');
        $this->validate(false, '-domain.com');
        $this->validate(false, 'domain-.com');
        $this->validate(false, 'do--main.com');
    }

    public function testIDN()
    {
        $this->validate(true, 'bürger.de');
        $this->validate(true, 'hãllo.de');
        $this->validate(true, 'hållo.se');
        $this->validate(true, 'bÜrger.de');
        $this->validate(true, 'hÃllo.de');
        $this->validate(true, 'hÅllo.se');
        $this->validate(true, 'hãllo.se');
        $this->validate(true, 'bürger.lt');
        $this->validate(true, 'hãllo.uk');
    }

    public function testNumberNames()
    {
        $this->validate(true, 'www.danger1.com');
        $this->validate(true, 'danger.com');
        $this->validate(true, 'www.danger.com');
        $this->validate(true, 'www.danger1com');
        $this->validate(true, 'dangercom');
        $this->validate(true, 'www.dangercom');
    }

    public function testLatinSpecialChars()
    {
        $this->validate(false, 'place@yah&oo.com');
        $this->validate(false, 'place@y*ahoo.com');
        $this->validate(false, 'ya#hoo');
    }

    public function testInvalidDoubledIdn()
    {
        $this->validate(false, 'test.com / http://www.test.com');
    }

    public function testTrailingDot()
    {
        $this->validate(true, 'example.');
        $this->validate(true, 'example.com.');
        $this->validate(true, '1.2.3.4.');
        $this->validate(true, 'example.');
        $this->validate(true, 'example.com.');
        $this->validate(false, 'example..');
        $this->validate(false, 'example..');
        $this->validate(false, '~ex%20ample..');
        $this->validate(false, '~ex%20ample.com.');
    }

    public function testZeroSubdomain()
    {
        $this->validate(true, '1.pool.ntp.org');
        $this->validate(true, '0.pool.ntp.org');
    }
}
