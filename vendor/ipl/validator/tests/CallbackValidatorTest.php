<?php

namespace ipl\Tests\Validator;

use ipl\Validator\CallbackValidator;

class CallbackValidatorTest extends TestCase
{
    public function testWhetherValidationCallbackIsOnlyExecutedWhenIsValidIsCalled()
    {
        $messages = ['Too short', 'Must contain only digits'];

        $validator = new CallbackValidator(function ($value, CallbackValidator $validator) use ($messages) {
            $validator->setMessages($messages);

            return $value;
        });

        $this->assertSame([], $validator->getMessages());

        $validator->isValid(true);

        $this->assertSame($messages, $validator->getMessages());
    }
}
