<?php

namespace ipl\Tests\Validator;

use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Validator\CidrValidator;

class CidrValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        StaticTranslator::$instance = new NoopTranslator();
    }

    public function testIPv4Validation()
    {
        $validator = new CidrValidator();

        $this->assertFalse($validator->isValid('127.0.0.0.1'));
        $this->assertFalse($validator->isValid('127.0.0.0.1/8'));
        $this->assertFalse($validator->isValid('127.0.0.1'));
        $this->assertFalse($validator->isValid('localhost/23'));
        $this->assertFalse($validator->isValid('127.0.0.1/test'));
        $this->assertFalse($validator->isValid('127.0.0.1/64'));
        $this->assertFalse($validator->isValid('127.0.0.1/-1'));

        $this->assertTrue($validator->isValid('127.0.0.1/8'));
        $this->assertTrue($validator->isValid('127.0.0.1/32'));
        $this->assertTrue($validator->isValid('127.0.0.1/0'));
    }

    public function testIPv6Validation()
    {
        $validator = new CidrValidator();

        $this->assertFalse($validator->isValid('0:0:0:0:0:0:0:00:0:0:0'));
        $this->assertFalse($validator->isValid('0:0:0:0:0:0:0:0:0:0:0:0/128'));
        $this->assertFalse($validator->isValid('0:0:0:0:0:0:0:0'));
        $this->assertFalse($validator->isValid('localhost:IPV6/128'));
        $this->assertFalse($validator->isValid('0:0:0:0:0:0:0:0/IPV6'));
        $this->assertFalse($validator->isValid('0:0:0:0:0:0:0:0/192'));
        $this->assertFalse($validator->isValid('0:0:0:0:0:0:0:0/-1'));

        $this->assertTrue($validator->isValid('0:0:0:0:0:0:0:0/64'));
        $this->assertTrue($validator->isValid('0:0:0:0:0:0:0:0/128'));
        $this->assertTrue($validator->isValid('0:0:0:0:0:0:0:0/0'));
    }
}
