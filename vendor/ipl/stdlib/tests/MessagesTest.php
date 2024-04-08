<?php

namespace ipl\Tests\Stdlib;

use ipl\Stdlib\Messages;

class MessagesTest extends TestCase
{
    public function testGetNoMessagesWhenEmpty()
    {
        $this->assertEmpty($this->getMessagesMock()->getMessages());
    }

    public function testMessagesCanBeAdded()
    {
        $this->assertSame(
            ['Message 1', 'Message 2'],
            $this
                ->getMessagesMock()
                ->addMessage('Message 1')
                ->addMessage(('Message 2'))
                ->getMessages()
        );
    }

    public function testMessagesCanBeCleared()
    {
        $this->assertEmpty(
            $this
                ->getMessagesMock()
                ->addMessage('Message 1')
                ->addMessage(('Message 2'))
                ->clearMessages()
                ->getMessages()
        );
    }

    /**
     * @return Messages|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMessagesMock()
    {
        return $this->getMockForTrait(Messages::class);
    }
}
