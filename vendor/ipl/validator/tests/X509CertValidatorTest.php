<?php

namespace ipl\Tests\Validator;

use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Validator\X509CertValidator;

class X509CertValidatorTest extends TestCase
{
    const CERT = <<<'EOF'
-----BEGIN CERTIFICATE-----
MIIBAzCBrgIBKjANBgkqhkiG9w0BAQQFADANMQswCQYDVQQDDAI0MjAeFw0yMTA1
MTcxMDI3MDlaFw0yMTA1MTgxMDI3MDlaMA0xCzAJBgNVBAMMAjQyMFwwDQYJKoZI
hvcNAQEBBQADSwAwSAJBANkBa53UGhd9RYiAZPGOz0/Y9P4/o6uHw/Eh4ExgCrpx
17NNV1JSAQlVnHtVANGmdz9J0c0MWC2ya3o39BbK7/cCAwEAATANBgkqhkiG9w0B
AQQFAANBACma7rGAI3khftF9du1KwivWzeGPHJwZBMfL/F99d2ckTyQozLTTL/p3
U1aTnHBR8cl5yTMAD8onBa/j7HhvL/Q=
-----END CERTIFICATE-----
EOF;

    public function testIsValidClearsPreviousMessagesIfInvalid()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $validator = new X509CertValidator();
        $validator->addMessage('will disappear');
        $validator->isValid(preg_replace('/[A-Z]/', '%', self::CERT));

        $this->assertSame(['Not a valid PEM-encoded X.509 certificate'], $validator->getMessages());
    }

    public function testIsValidClearsPreviousMessagesIfValid()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $validator = new X509CertValidator();
        $validator->addMessage('will disappear');
        $validator->isValid(self::CERT);

        $this->assertSame([], $validator->getMessages());
    }

    public function testIsValidDisallowsUrls()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid() . '.pem';
        $mask = umask(0700);

        file_put_contents($tempFile, self::CERT);
        umask($mask);

        $this->assertSame(false, (new X509CertValidator())->isValid("file://$tempFile"));
    }

    public function testIsValidActuallyValidatesIfInvalid()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $this->assertSame(false, (new X509CertValidator())->isValid(preg_replace('/[A-Z]/', '%', self::CERT)));
    }

    public function testIsValidActuallyValidatesIfValid()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $this->assertSame(true, (new X509CertValidator())->isValid(self::CERT));
    }
}
