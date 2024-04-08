<?php

namespace ipl\Tests\Stdlib;

use ipl\Stdlib\PriorityQueue;

class PriorityQueueTest extends TestCase
{
    public function testInsertMaintainsInsertionOrderForItemsWithTheSamePriority()
    {
        $queue = new PriorityQueue();

        $queue->insert(1, 1);
        $queue->insert(2, 1);
        $queue->insert(3, 1);

        $expected = 1;

        foreach ($queue as $item) {
            $this->assertSame($expected++, $item);
        }
    }

    public function testYieldAllYieldsTheOriginalPriorityAndItem()
    {
        $queue = new PriorityQueue();

        $queue->insert(1, 1);
        $queue->insert(2, 1);
        $queue->insert(3, 1);

        $expected = 1;

        foreach ($queue->yieldAll() as $priority => $item) {
            $this->assertSame(1, $priority);
            $this->assertSame($expected++, $item);
        }
    }

    public function testYieldAllDoesNotConsumeTheQueue()
    {
        $queue = new PriorityQueue();

        $queue->insert(1, 1);
        $queue->insert(2, 1);
        $queue->insert(3, 1);

        foreach ($queue->yieldAll() as $priority => $item) {
        }

        $expected = 1;

        foreach ($queue->yieldAll() as $priority => $item) {
            $this->assertSame(1, $priority);
            $this->assertSame($expected++, $item);
        }
    }
}
