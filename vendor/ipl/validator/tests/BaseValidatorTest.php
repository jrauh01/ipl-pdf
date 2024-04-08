<?php

namespace ipl\Tests\Validator;

class BaseValidatorTest extends TestCase
{
    public function testGetMessagesReturnsAnEmptyArrayIfNoMessageHasBeenAdded()
    {
        $this->assertSame([], (new TestValidator())->getMessages());
    }

    public function testGetMessagesReturnsMessagesPopulatedViaSetMessages()
    {
        $messages = ['Too short', 'Must contain only digits'];

        $validator = new TestValidator();
        $validator->setMessages($messages);

        $this->assertSame($messages, $validator->getMessages());
    }

    public function testGetMessagesReturnsMessagesPopulatedViaAddMessage()
    {
        $messages = ['Too short', 'Must contain only digits'];

        $validator = new TestValidator();

        foreach ($messages as $message) {
            $validator->addMessage($message);
        }

        $this->assertSame($messages, $validator->getMessages());
    }
}
