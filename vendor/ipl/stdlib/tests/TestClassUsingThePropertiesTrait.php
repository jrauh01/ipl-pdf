<?php

namespace ipl\Tests\Stdlib;

use ipl\Stdlib\Properties;

class TestClassUsingThePropertiesTrait implements \ArrayAccess, \IteratorAggregate
{
    use Properties;
}
