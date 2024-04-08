<?php

namespace ipl\Tests\Stdlib;

use ipl\Stdlib\Data;

class DataTest extends \PHPUnit\Framework\TestCase
{
    public function testDataIsEmpty()
    {
        $data = new Data();

        $this->assertTrue($data->isEmpty());
    }

    public function testDataHas()
    {
        $data = new Data();

        $this->assertFalse($data->has('foo'));

        $data->set('foo', 'bar');

        $this->assertTrue($data->has('foo'));
    }

    public function testDataGet()
    {
        $data = new Data();

        $this->assertNull($data->get('foo'));
        $this->assertEquals('oof', $data->get('foo', 'oof'));

        $data->set('foo', 'bar');

        $this->assertEquals('bar', $data->get('foo'));
    }

    public function testDataMerge()
    {
        $data = new Data();

        $toMerge = new Data();
        $toMerge->set('foo', 'bar');

        $this->assertNull($data->get('foo'));

        $data->merge($toMerge);

        $this->assertEquals('bar', $data->get('foo'));
    }

    public function testDataClear()
    {
        $data = new Data();
        $data->set('foo', 'bar');

        $this->assertFalse($data->isEmpty());

        $data->clear();

        $this->assertTrue($data->isEmpty());
    }
}
