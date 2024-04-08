<?php

namespace ipl\Tests\Validator;

use GuzzleHttp\Psr7\UploadedFile;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Validator\FileValidator;

class FileValidatorTest extends TestCase
{
    public function createUploadedFileObject($mimeType = 'application/pdf'): UploadedFile
    {
        return new UploadedFile(
            'test/test.pdf',
            500,
            0,
            'test.pdf',
            $mimeType
        );
    }

    public function testValidValue(): void
    {
        StaticTranslator::$instance = new NoopTranslator();

        $validator = new FileValidator();

        $uploadedFile = $this->createUploadedFileObject();

        $this->assertTrue($validator->isValid($uploadedFile));
    }

    public function testArrayAsValue(): void
    {
        StaticTranslator::$instance = new NoopTranslator();

        $validator = new FileValidator();

        $files = [
            $this->createUploadedFileObject(),
            $this->createUploadedFileObject()
        ];

        $this->assertTrue($validator->isValid($files));
    }

    public function testMinSizeOption(): void
    {
        StaticTranslator::$instance = new NoopTranslator();

        $uploadedFile = $this->createUploadedFileObject();

        $validator = new FileValidator(['minSize' => 10]);

        $this->assertTrue($validator->isValid($uploadedFile));

        $validator->setMinSize(700);
        $this->assertFalse($validator->isValid($uploadedFile));
    }

    public function testMaxSizeOption(): void
    {
        StaticTranslator::$instance = new NoopTranslator();

        $uploadedFile = $this->createUploadedFileObject();

        $validator = new FileValidator(['maxSize' => 700]);

        $this->assertTrue($validator->isValid($uploadedFile));

        $validator->setMaxSize(300);
        $this->assertFalse($validator->isValid($uploadedFile));
    }

    public function testMimeTypeOption(): void
    {
        StaticTranslator::$instance = new NoopTranslator();

        $uploadedFile = $this->createUploadedFileObject();

        $validator = (new FileValidator())
            ->setAllowedMimeTypes(['application/pdf']);

        $this->assertTrue($validator->isValid($uploadedFile));

        $validator->setAllowedMimeTypes(['application/*']);

        $this->assertTrue($validator->isValid($uploadedFile));

        $validator->setAllowedMimeTypes(['image/gif', 'image/jpeg']);
        $uploadedFile = $this->createUploadedFileObject('image/png');

        $this->assertFalse($validator->isValid($uploadedFile));
    }

    public function testMaxFileNameLengthOption(): void
    {
        StaticTranslator::$instance = new NoopTranslator();

        $uploadedFile = $this->createUploadedFileObject();

        $validator = new FileValidator(['maxFileNameLength' => 10]);

        $this->assertTrue($validator->isValid($uploadedFile));

        $validator->setMaxFileNameLength(3);
        $this->assertFalse($validator->isValid($uploadedFile));
    }
}
