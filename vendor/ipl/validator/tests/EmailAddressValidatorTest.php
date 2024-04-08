<?php

namespace ipl\Tests\Validator;

use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Validator\EmailAddressValidator;

class EmailAddressValidatorTest extends TestCase
{
    /** @var EmailAddressValidator */
    protected $validator;

    public function setUp(): void
    {
        $this->validator = new EmailAddressValidator();
        StaticTranslator::$instance = new NoopTranslator();
    }

    protected function validate($expected, $value, $validator = null)
    {
        $validator = $validator ?? $this->validator;
        $this->assertSame($expected, $validator->isValid($value), implode("\n", $validator->getMessages()));
    }

    public function testEmailAddressValidatorWithInvalidIpAsHostName()
    {
        $this->validate(false, 'test@[580.0.0.0]');
    }

    public function testEmailAddressValidatorWithValidDeepMXRecordValidation()
    {
        $validator = new EmailAddressValidator(['mx' => true, 'deep' => true]);
        $this->validate(true, "info@icinga.com", $validator);
    }

    public function testEmailAddressValidatorWithInvalidDeepMXRecordValidation()
    {
        $validator = new EmailAddressValidator(['mx' => true, 'deep' => true]);
        $this->validate(false, "test@example.com", $validator);
    }

    public function testBasic()
    {
        $this->validate(true, 'username@example.com');
    }

    public function testLocalhostAllowed()
    {
        $this->validate(true, 'username@localhost');
    }

    public function testLocaldomainAllowed()
    {
        $this->validate(true, 'username@localhost.localdomain');
    }

    public function testIPAllowed()
    {
        $this->validate(true, 'bob@212.212.20.4');
        $this->validate(true, 'bob@[212.212.20.4]');
    }

    public function testLocalPartMissing()
    {
        $this->validate(false, '@example.com');
    }

    public function testLocalPartInvalid()
    {
        $this->validate(false, 'Some User@example.com');
    }

    public function testLocalPartQuotedString()
    {
        $this->validate(true, '"Some User"@example.com');
    }

    public function testHostnameInvalid()
    {
        $this->validate(false, 'username@ example . com');
    }

    public function testQuotedString()
    {
        $emailAddresses = [
            '""@domain.com', // Optional
            '" "@domain.com', // x20
            '"!"@domain.com', // x21
            '"\""@domain.com', // \" (escaped x22)
            '"#"@domain.com', // x23
            '"$"@domain.com', // x24
            '"Z"@domain.com', // x5A
            '"["@domain.com', // x5B
            '"\\\"@domain.com', // \\ (escaped x5C)
            '"]"@domain.com', // x5D
            '"^"@domain.com', // x5E
            '"}"@domain.com', // x7D
            '"~"@domain.com', // x7E
            '"username"@example.com',
            '"bob%jones"@domain.com',
            '"bob jones"@domain.com',
            '"bob@jones"@domain.com',
            '"[[ bob ]]"@domain.com',
            '"jones"@domain.com'
        ];
        foreach ($emailAddresses as $input) {
            $this->validate(true, $input);
        }
    }

    public function testInvalidQuotedString()
    {
        $emailAddresses = [
            "\"\x00\"@example.com",
            "\"\x01\"@example.com",
            "\"\x1E\"@example.com",
            "\"\x1F\"@example.com",
            '"""@example.com', // x22 (not escaped)
            '"\"@example.com', // x5C (not escaped)
            "\"\x7F\"@example.com",
        ];
        foreach ($emailAddresses as $input) {
            $this->validate(false, $input);
        }
    }

    public function testEmailDisplay()
    {
        $this->validate(false, 'User Name <username@example.com>');
    }

    public function testBasicValid()
    {
        $emailAddresses = [
            'bob@domain.com',
            'bob.jones@domain.co.uk',
            'bob.jones.smythe@domain.co.uk',
            'BoB@domain.museum',
            'bobjones@domain.info',
            "B.O'Callaghan@domain.com",
            'bob+jones@domain.us',
            'bob+jones@domain.co.uk',
            'bob@some.domain.uk.com',
            'bob@verylongdomainsupercalifragilisticexpialidociousspoonfulofsugar.com'
        ];
        foreach ($emailAddresses as $input) {
            $this->validate(true, $input);
        }
    }

    public function testBasicInvalid()
    {
        $emailAddresses = [
            '',
            'bob

            @domain.com',
            'bob jones@domain.com',
            '.bobJones@studio24.com',
            'bobJones.@studio24.com',
            'bob.Jones.@studio24.com',
            '"bob%jones@domain.com',
            'bob@verylongdomainsupercalifragilisticexpialidociousaspoonfulofsugar.com',
            'bob+domain.com',
            'bob.domain.com',
            'bob @domain.com',
            'bob@ domain.com',
            'bob @ domain.com',
            'Abc..123@example.com'
        ];
        foreach ($emailAddresses as $input) {
            $this->validate(false, $input);
        }
    }

    public function testComplexLocalValid()
    {
        $emailAddresses = [
            'Bob.Jones@domain.com',
            'Bob.Jones!@domain.com',
            'Bob&Jones@domain.com',
            '/Bob.Jones@domain.com',
            '#Bob.Jones@domain.com',
            'Bob.Jones?@domain.com',
            'Bob~Jones@domain.com'
        ];
        foreach ($emailAddresses as $input) {
            $this->validate(true, $input);
        }
    }

    public function testMXRecords()
    {
        $validator = new EmailAddressValidator(['mx' => true]);

        $this->validate(true, 'Bob.Jone!s@zend.com', $validator);
        $this->validate(true, 'Bob.Jones@php.net', $validator);
        $this->validate(false, 'Bob.Jones@bad.example.com', $validator);
        $this->validate(false, 'Bob.Jones@anotherbad.example.com', $validator);
    }

    public function testSupportsIpv6AddressesWhichContainHexDigitF()
    {
        $this->validate(true, 'test@FEDC:BA98:7654:3210:FEDC:BA98:7654:3210');
        $this->validate(true, 'test@1080:0:0:0:8:800:200C:417A');
        $this->validate(true, 'test@3ffe:2a00:100:7031::1');
        $this->validate(true, 'test@1080::8:800:200C:417A');
        $this->validate(true, 'test@::192.9.5.5');
        $this->validate(true, 'test@::FFFF:129.144.52.38');
        $this->validate(true, 'test@2010:836B:4179::836B:4179');
    }

    public function testEmailsExceedingLength()
    {
        $this->validate(
            false,
            'thislocalpathoftheemailadressislongerthantheallowedsizeof64characters@domain.com'
        );
        $this->validate(
            false,
            'bob@verylongdomainsupercalifragilisticexpialidociousspoonfulofsugarverylongdomainsupercalifragilisticexpia'
            . 'lidociousspoonfulofsugarverylongdomainsupercalifragilisticexpialidociousspoonfulofsugarverylongdomainsup'
            . 'ercalifragilisticexpialidociousspoonfulofsugarexpialidociousspoonfulofsugar.com'
        );
    }

    public function testIdnHostnameInEmaillAddress()
    {
        $validator = new EmailAddressValidator(['mx' => true]);
        $this->validate(true, 'info@icinga.com', $validator);
        $this->validate(false, 'info@34rtzghjk.com', $validator);
    }

    public function testNonReservedIp()
    {
        $this->validate(true, 'bob@192.162.33.24');
    }
}
