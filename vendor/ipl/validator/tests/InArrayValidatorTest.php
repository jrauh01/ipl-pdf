<?php

namespace ipl\Tests\Validator;

use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Tests\Validator\Lib\InArrayValidatorWithPublicFindInvalid;
use ipl\Validator\InArrayValidator;

class InArrayValidatorTest extends TestCase
{
    public function testValidValues()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $validator = new InArrayValidator([
            'haystack' => ['test', 'foo', 'bar', 5, [], 0.008]
        ]);

        $this->assertTrue($validator->isValid('foo'), 'foo was not found in the haystack');
        $this->assertTrue($validator->isValid(5), '5 was not found in the haystack');
        $this->assertTrue($validator->isValid(0.008), '00.8 was not found in the haystack');
        $this->assertTrue($validator->isValid([]), '[] (empty array) was not found in the haystack');
    }

    public function testInvalidValues()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $validator = new InArrayValidator([
            'haystack' => ['test', 'foo', 'bar', 5, [], 0.008]
        ]);

        $this->assertFalse($validator->isValid('bear'), 'bear was found in the haystack');
        $this->assertFalse($validator->isValid(60), '60 was found in the haystack');
        $this->assertFalse($validator->isValid(0.09), '0.09 was found in the haystack');
    }

    public function testStrictOption()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $validator = new InArrayValidator([
            'haystack' => ['test', 'foo', 'bar', 5, [], 0.008, '295'],
            'strict'    => true
        ]);

        $this->assertTrue($validator->isValid('295'), '"295" was not found in the haystack');
        $this->assertFalse($validator->isValid(295), '295 (int) was found in the haystack');
        $this->assertFalse($validator->isValid('0.008'), '"0.008" was found in the haystack');
    }

    public function testArrayAsValue()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $validator = new InArrayValidator([
            'haystack'  => ['test', 'foo', 'bar', 5, 0.008, '295'],
        ]);

        $this->assertFalse(
            $validator->isValid(['car', 'cat', 55]),
            '"car", "cat", 55 were found in the haystack'
        );

        $this->assertTrue(
            $validator->isValid(['test', '295', 0.008]),
            '"test", "295", 0.008 were not found in the haystack'
        );
    }

    public function testOptions()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $validator = new InArrayValidator([
            'haystack'  => ['test', 'foo', 'bar', 5, [], 0.008],
            'strict'    => true
        ]);

        $this->assertTrue($validator->isStrict());

        $validator->setStrict(false);
        $this->assertFalse($validator->isStrict());

        $this->assertSame(['test', 'foo', 'bar', 5, [], 0.008], $validator->getHaystack());

        $validator->setHaystack([]);
        $this->assertSame([], $validator->getHaystack());
    }

    public function testFindInvalid()
    {
        StaticTranslator::$instance = new NoopTranslator();
        $validator = (new InArrayValidatorWithPublicFindInvalid())
            ->setHaystack(['a', 'b', 'c']);

        $this->assertSame([], $validator->findInvalid(['a', 'b', 'c']));
        $this->assertSame([''], $validator->findInvalid(['a', 'b', 'c', '']));
        $this->assertSame(['d'], $validator->findInvalid(['a', 'd']));
        $this->assertSame([55, 'foo'], $validator->findInvalid([55, 'foo']));
    }
}
