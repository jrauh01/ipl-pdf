<?php

namespace ipl\Tests\Validator;

use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Validator\DeferredInArrayValidator;

class DeferredInArrayValidatorTest extends TestCase
{
    public function testValidAndInvalidValue()
    {
        StaticTranslator::$instance = new NoopTranslator();

        $validator = new DeferredInArrayValidator(function () {
            return ['test', 'foo', 'bar', 5, [], 0.008];
        });

        $this->assertTrue($validator->isValid('foo'), 'foo was not found in the haystack');
        $this->assertTrue($validator->isValid(5), '5 was not found in the haystack');

        $this->assertFalse($validator->isValid('bear'), 'bear was found in the haystack');
        $this->assertFalse($validator->isValid(60), '60 was found in the haystack');
    }

    public function testSetCallbackOverwitesTheHaystack()
    {
        $validator = new DeferredInArrayValidator(function () {
            return ['test', 'foo', 'bar'];
        });

        $this->assertSame(['test', 'foo', 'bar'], $validator->getHaystack());

        $validator->setCallback(function () {
            return ['a', 'b', 'c'];
        });

        $this->assertSame(['a', 'b', 'c'], $validator->getHaystack());
    }
}
