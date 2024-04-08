<?php

namespace ipl\Tests\Validator;

use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Validator\PrivateKeyValidator;

class PrivateKeyValidatorTest extends TestCase
{
    const KEY = <<<'EOF'
-----BEGIN PRIVATE KEY-----
MIIBVQIBADANBgkqhkiG9w0BAQEFAASCAT8wggE7AgEAAkEA2QFrndQaF31FiIBk
8Y7PT9j0/j+jq4fD8SHgTGAKunHXs01XUlIBCVWce1UA0aZ3P0nRzQxYLbJrejf0
Fsrv9wIDAQABAkEAm0xV7MRey9Kd0Vs5Ylm2aUk1w0Jd6iKmCkoZD+9nnhcKSNuR
Jf3I9OAXYWCOIEszfrFyAQDTdp9UrOyeE9U7SQIhAP7cREMVA0NryBqYwJketN54
3unUJGBkVeumyXA/EMIFAiEA2fnScmRn4cXqqxe9Dkgn2RiogTkCZ8h5BdY67xta
nssCIF6gT+QMUDrfMNvXLWNsyED15eYxsxPrDQ/CzHYVpFY1AiEAz080gatQyX+s
kpB/NCgYDffPuyb3TLFzuMNpRaOkakUCIHtBnos4xywZBqDdRIenbxRdQHX/llUx
r1WLl8RkIQ3V
-----END PRIVATE KEY-----
EOF;

    public function testIsValidClearsPreviousMessagesIfInvalid()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $validator = new PrivateKeyValidator();
        $validator->addMessage('will disappear');
        $validator->isValid(preg_replace('/[A-Z]/', '%', self::KEY));

        $this->assertSame(['Not a valid PEM-encoded private key'], $validator->getMessages());
    }

    public function testIsValidClearsPreviousMessagesIfValid()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $validator = new PrivateKeyValidator();
        $validator->addMessage('will disappear');
        $validator->isValid(self::KEY);

        $this->assertSame([], $validator->getMessages());
    }

    public function testIsValidDisallowsUrls()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid() . '.pem';
        $mask = umask(0700);

        file_put_contents($tempFile, self::KEY);
        umask($mask);

        $this->assertSame(false, (new PrivateKeyValidator())->isValid("file://$tempFile"));
    }

    public function testIsValidActuallyValidatesIfInvalid()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $this->assertSame(false, (new PrivateKeyValidator())->isValid(preg_replace('/[A-Z]/', '%', self::KEY)));
    }

    public function testIsValidActuallyValidatesIfValid()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $this->assertSame(true, (new PrivateKeyValidator())->isValid(self::KEY));
    }
}
