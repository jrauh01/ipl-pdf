<?php

namespace ipl\Tests\Stdlib;

use ipl\Stdlib\Filter;

class FilterTest extends TestCase
{
    private $sampleData = [
        [
            'host'    => 'localhost',
            'problem' => '1',
            'service' => 'ping',
            'state'   => 2,
            'handled' => '1'
        ],
        [
            'host'    => 'localhost',
            'problem' => '1',
            'service' => 'www.icinga.com',
            'state'   => 0,
            'handled' => '0'
        ],
        [
            'host'    => 'localhost',
            'problem' => '1',
            'service' => 'www.icinga.com',
            'state'   => 1,
            'handled' => '0'
        ]
    ];

    private function row($id)
    {
        return (object) $this->sampleData[$id];
    }

    public function testAllMatches()
    {
        $all = Filter::all(
            Filter::equal('problem', '1'),
            Filter::equal('handled', '1')
        );

        $this->assertTrue(Filter::match($all, $this->row(0)));
    }

    public function testAllMismatches()
    {
        $all = Filter::all(
            Filter::equal('problem', '1'),
            Filter::equal('handled', '1')
        );

        $this->assertFalse(Filter::match($all, $this->row(1)));
    }

    public function testAnyMatches()
    {
        $any = Filter::any(
            Filter::equal('problem', '1'),
            Filter::equal('handled', '1')
        );

        $this->assertTrue(Filter::match($any, $this->row(1)));
    }

    public function testAnyMismatches()
    {
        $any = Filter::any(
            Filter::equal('problem', '0'),
            Filter::equal('handled', '1')
        );

        $this->assertFalse(Filter::match($any, $this->row(1)));
    }

    public function testNoneMatches()
    {
        $none = Filter::none(
            Filter::equal('problem', '0'),
            Filter::equal('handled', '1')
        );

        $this->assertTrue(Filter::match($none, $this->row(2)));
    }

    public function testNoneMismatches()
    {
        $none = Filter::none(
            Filter::equal('problem', '1'),
            Filter::equal('handled', '0')
        );

        $this->assertFalse(Filter::match($none, $this->row(2)));
    }

    public function testEqualMatches()
    {
        $equal = Filter::equal('problem', '1');

        $this->assertTrue(Filter::match($equal, $this->row(0)));
    }

    public function testEqualMismatches()
    {
        $equal = Filter::equal('handled', '1');

        $this->assertFalse(Filter::match($equal, $this->row(1)));
    }

    public function testEqualIgnoresCase()
    {
        // single string
        $equal = Filter::equal('host', 'LOCALHOST')
            ->ignoreCase();

        $this->assertTrue(Filter::match($equal, $this->row(0)));

        // string array
        $equal->setValue(['LoCaLhOsT', '127.0.0.1']);

        $this->assertTrue(Filter::match($equal, $this->row(0)));
    }

    public function testLikeMatches()
    {
        $like = Filter::like('problem', '1');

        $this->assertTrue(Filter::match($like, $this->row(0)));
    }

    public function testLikeMismatches()
    {
        $like = Filter::like('handled', '1');

        $this->assertFalse(Filter::match($like, $this->row(1)));
    }

    public function testLikeIgnoresCase()
    {
        // single string
        $like = Filter::like('host', '*LOCAL*')
            ->ignoreCase();

        $this->assertTrue(Filter::match($like, $this->row(0)));

        // string array
        $like->setValue(['LoCaLhOsT', '127.0.0.1']);

        $this->assertTrue(Filter::match($like, $this->row(0)));
    }

    public function testEqualMatchesMultiValuedColumns()
    {
        $this->assertTrue(Filter::match(Filter::equal('foo', 'bar'), [
            'foo' => ['foo', 'bar']
        ]));
        $this->assertTrue(Filter::match(Filter::equal('foo', 'BAR')->ignoreCase(), [
            'foo' => ['FoO', 'bAr']
        ]));
        $this->assertTrue(Filter::match(Filter::equal('foo', ['bar', 'boar']), [
            'foo' => ['foo', 'bar']
        ]));
    }

    public function testLikeMatchesMultiValuedColumns()
    {
        $this->assertTrue(Filter::match(Filter::like('foo', 'bar'), [
            'foo' => ['foo', 'bar']
        ]));
        $this->assertTrue(Filter::match(Filter::like('foo', 'ba*'), [
            'foo' => ['foo', 'bar']
        ]));
        $this->assertTrue(Filter::match(Filter::like('foo', 'BAR')->ignoreCase(), [
            'foo' => ['FoO', 'bAr']
        ]));
        $this->assertTrue(Filter::match(Filter::like('foo', ['bar', 'boar']), [
            'foo' => ['foo', 'bar']
        ]));
    }

    public function testUnequalMatches()
    {
        $unequal = Filter::unequal('problem', '0');

        $this->assertTrue(Filter::match($unequal, $this->row(1)));
    }

    public function testUnequalMismatches()
    {
        $unequal = Filter::unequal('problem', '1');

        $this->assertFalse(Filter::match($unequal, $this->row(1)));
    }

    public function testUnequalIgnoresCase()
    {
        // single string
        $equal = Filter::unequal('host', 'LOCALHOST')
            ->ignoreCase();

        $this->assertFalse(Filter::match($equal, $this->row(0)));

        // string array
        $equal->setValue(['LoCaLhOsT', '127.0.0.1']);

        $this->assertFalse(Filter::match($equal, $this->row(0)));
    }

    public function testUnlikeMatches()
    {
        $unlike = Filter::unlike('problem', '0');

        $this->assertTrue(Filter::match($unlike, $this->row(1)));
    }

    public function testUnlikeMismatches()
    {
        $unlike = Filter::unlike('problem', '1');

        $this->assertFalse(Filter::match($unlike, $this->row(1)));
    }

    public function testUnlikeIgnoresCase()
    {
        // single string
        $unlike = Filter::unlike('host', '*LOCAL*')
            ->ignoreCase();

        $this->assertFalse(Filter::match($unlike, $this->row(0)));

        // string array
        $unlike->setValue(['LoCaLhOsT', '127.0.0.1']);

        $this->assertFalse(Filter::match($unlike, $this->row(0)));
    }

    public function testUnequalMatchesMultiValuedColumns()
    {
        $this->assertFalse(Filter::match(Filter::unequal('foo', 'bar'), [
            'foo' => ['foo', 'bar']
        ]));
        $this->assertFalse(Filter::match(Filter::unequal('foo', 'BAR')->ignoreCase(), [
            'foo' => ['FoO', 'bAr']
        ]));
        $this->assertFalse(Filter::match(Filter::unequal('foo', ['bar', 'boar']), [
            'foo' => ['foo', 'bar']
        ]));
    }

    public function testUnlikeMatchesMultiValuedColumns()
    {
        $this->assertFalse(Filter::match(Filter::unlike('foo', 'bar'), [
            'foo' => ['foo', 'bar']
        ]));
        $this->assertFalse(Filter::match(Filter::unlike('foo', 'ba*'), [
            'foo' => ['foo', 'bar']
        ]));
        $this->assertFalse(Filter::match(Filter::unlike('foo', 'BAR')->ignoreCase(), [
            'foo' => ['FoO', 'bAr']
        ]));
        $this->assertFalse(Filter::match(Filter::unlike('foo', ['bar', 'boar']), [
            'foo' => ['foo', 'bar']
        ]));
    }

    public function testGreaterThanMatches()
    {
        $greaterThan = Filter::greaterThan('state', 1);

        $this->assertTrue(Filter::match($greaterThan, $this->row(0)));
    }

    public function testGreaterThanMismatches()
    {
        $greaterThan = Filter::greaterThan('state', 1);

        $this->assertFalse(Filter::match($greaterThan, $this->row(2)));
    }

    public function testGreaterThanOrEqualMatches()
    {
        $greaterThanOrEqual = Filter::greaterThanOrEqual('state', 1);

        $this->assertTrue(Filter::match($greaterThanOrEqual, $this->row(0)));
        $this->assertTrue(Filter::match($greaterThanOrEqual, $this->row(2)));
    }

    public function testGreaterThanOrEqualMismatches()
    {
        $greaterThanOrEqual = Filter::greaterThanOrEqual('state', 2);

        $this->assertFalse(Filter::match($greaterThanOrEqual, $this->row(1)));
        $this->assertFalse(Filter::match($greaterThanOrEqual, $this->row(2)));
    }

    public function testLessThanMatches()
    {
        $lessThan = Filter::lessThan('state', 1);

        $this->assertTrue(Filter::match($lessThan, $this->row(1)));
    }

    public function testLessThanMismatches()
    {
        $lessThan = Filter::lessThan('state', 2);

        $this->assertFalse(Filter::match($lessThan, $this->row(0)));
    }

    public function testLessThanOrEqualMatches()
    {
        $lessThanOrEqual = Filter::lessThanOrEqual('state', 1);

        $this->assertTrue(Filter::match($lessThanOrEqual, $this->row(1)));
        $this->assertTrue(Filter::match($lessThanOrEqual, $this->row(2)));
    }

    public function testLessThanOrEqualMismatches()
    {
        $lessThanOrEqual = Filter::lessThanOrEqual('state', 0);

        $this->assertFalse(Filter::match($lessThanOrEqual, $this->row(0)));
        $this->assertFalse(Filter::match($lessThanOrEqual, $this->row(2)));
    }

    public function testEqualWithWildcardMismatches()
    {
        $equal = Filter::equal('service', '*icinga*');

        $this->assertFalse(Filter::match($equal, $this->row(1)));
    }

    public function testLikeWithWildcardMatches()
    {
        $like = Filter::like('service', '*icinga*');

        $this->assertTrue(Filter::match($like, $this->row(1)));
    }

    public function testLikeWithWildcardMismatches()
    {
        $like = Filter::like('service', '*nagios*');

        $this->assertFalse(Filter::match($like, $this->row(1)));
    }

    public function testUnequalWithWildcardMatches()
    {
        $unequal = Filter::unequal('service', '*icinga*');

        $this->assertTrue(Filter::match($unequal, $this->row(1)));
    }

    public function testUnlikeWithWildcardMatches()
    {
        $unlike = Filter::unlike('service', '*nagios*');

        $this->assertTrue(Filter::match($unlike, $this->row(1)));
    }

    public function testUnlikeWithWildcardMismatches()
    {
        $unlike = Filter::unlike('service', '*icinga*');

        $this->assertFalse(Filter::match($unlike, $this->row(1)));
    }

    public function testEqualWithArrayMatches()
    {
        $equal = Filter::equal('host', ['127.0.0.1', 'localhost']);

        $this->assertTrue(Filter::match($equal, $this->row(0)));
    }

    public function testEqualWithArrayMismatches()
    {
        $equal = Filter::equal('host', ['10.0.10.20', '10.0.10.21']);

        $this->assertFalse(Filter::match($equal, $this->row(0)));
    }

    public function testLikeWithArrayMatches()
    {
        $like = Filter::like('host', ['127.0.0.1', 'localhost']);

        $this->assertTrue(Filter::match($like, $this->row(0)));
    }

    public function testLikeWithArrayMismatches()
    {
        $like = Filter::like('host', ['10.0.10.20', '10.0.10.21']);

        $this->assertFalse(Filter::match($like, $this->row(0)));
    }

    public function testUnequalWithArrayMatches()
    {
        $unequal = Filter::unequal('host', ['10.0.20.10', '10.0.20.11']);

        $this->assertTrue(Filter::match($unequal, $this->row(0)));
    }

    public function testUnlikeWithArrayMatches()
    {
        $unlike = Filter::unlike('host', ['10.0.20.10', '10.0.20.11']);

        $this->assertTrue(Filter::match($unlike, $this->row(0)));
    }

    public function testUnequalWithArrayMismatches()
    {
        $unequal = Filter::unequal('host', ['127.0.0.1', 'localhost']);

        $this->assertFalse(Filter::match($unequal, $this->row(0)));
    }

    public function testUnlikeWithArrayMismatches()
    {
        $unlike = Filter::unlike('host', ['127.0.0.1', 'localhost']);

        $this->assertFalse(Filter::match($unlike, $this->row(0)));
    }

    public function testConditionsAreValueTypeAgnostic()
    {
        $this->assertTrue(
            Filter::match(Filter::equal('name', ' foo '), ['name' => ' foo ']),
            "Filter\Equal doesn't take strings with whitespace as-is"
        );
        $this->assertTrue(
            Filter::match(Filter::equal('length', '19'), ['length' => 19]),
            "Filter\Equal fails to match strings with integers"
        );
        $this->assertTrue(
            Filter::match(Filter::equal('port', ['80', '8080']), ['port' => 8080]),
            "Filter\Equal fails to match string[] with integers"
        );
        $this->assertFalse(
            Filter::match(Filter::equal('active', 'foo'), ['active' => true]),
            "Filter\Equal doesn't differ between true strings and booleans"
        );
        $this->assertFalse(
            Filter::match(Filter::equal('active', ['foo', 'bar']), ['active' => true]),
            "Filter\Equal doesn't differ between true string[] and booleans"
        );
        $this->assertTrue(
            Filter::match(Filter::equal('active', 0), ['active' => false]),
            "Filter\Equal doesn't match false integers with booleans"
        );
        $this->assertTrue(
            Filter::match(Filter::equal('active', [true]), ['active' => 1]),
            "Filter\Equal doesn't match boolean[] with true integers"
        );
        $this->assertTrue(
            Filter::match(Filter::equal('some_id', null), ['some_id' => null]),
            "Filter\Equal fails to match NULL"
        );
        $this->assertFalse(
            Filter::match(Filter::equal('some_id', 0), ['some_id' => null]),
            "Filter\Equal doesn't compare NULL strictly"
        );
        $this->assertTrue(
            Filter::match(Filter::greaterThan('length', '21'), ['length' => 22]),
            "Filter\GreaterThan fails to match strings with integers"
        );
        $this->assertTrue(
            Filter::match(Filter::lessThan('length', '22'), ['length' => 21]),
            "Filter\LessThan fails to match strings with integers"
        );
    }

    public function testConditionsCanBeCloned()
    {
        $condition1 = Filter::equal('host', 'localhost');
        $condition2 = clone $condition1;
        $condition2->setColumn('service');
        $condition2->setValue('ping');

        $this->assertEquals('host', $condition1->getColumn());
        $this->assertEquals('localhost', $condition1->getValue());
    }

    public function testChainsCanBeCloned()
    {
        $chain1 = Filter::all(
            Filter::equal('host', 'localhost'),
            Filter::equal('problem', '1'),
            Filter::all(
                Filter::equal('handled', '0')
            )
        );

        $chain2 = clone $chain1;
        foreach ($chain2 as $rule) {
            if ($rule instanceof Filter\Chain) {
                $rule->add(Filter::equal('state', 1));
            }
        }

        $this->assertTrue(Filter::match($chain1, $this->row(1)));
        $this->assertTrue(Filter::match($chain2, $this->row(2)));
    }

    public function testChainsCanBeAdjusted()
    {
        $chain = Filter::any(
            Filter::equal('service', 'ping')
        );
        $this->assertFalse(Filter::match($chain, $this->row(1)));

        // add
        $stateEqualsZero = Filter::equal('state', 0);
        $chain->add($stateEqualsZero);
        $this->assertTrue(Filter::match($chain, $this->row(1)));
        $this->assertFalse(Filter::match($chain, $this->row(2)));

        // replace
        $stateEqualsOne = Filter::equal('state', 1);
        $chain->replace($stateEqualsZero, $stateEqualsOne);
        $this->assertTrue(Filter::match($chain, $this->row(2)));
        $this->assertFalse(Filter::match($chain, $this->row(1)));

        // insertBefore
        $chain->insertBefore($stateEqualsZero, $stateEqualsOne);
        $this->assertTrue(Filter::match($chain, $this->row(1)));
        $this->assertTrue(Filter::match($chain, $this->row(2)));

        // remove
        $chain->remove($stateEqualsOne);
        $this->assertFalse(Filter::match($chain, $this->row(2)));

        // insertAfter
        $hasProblem = Filter::equal('problem', '1');
        $chain->insertAfter($hasProblem, $stateEqualsZero);
        $this->assertTrue(Filter::match($chain, $this->row(2)));
    }

    public function testChainsCanBeEmpty()
    {
        $this->assertTrue(Filter::all()->isEmpty());
        $this->assertFalse(Filter::all(Filter::equal('a', 'b'))->isEmpty());
    }

    public function testConditionsHandleMissingColumnsProperly()
    {
        $this->assertFalse(Filter::match(Filter::equal('foo', 'bar'), []));
        $this->assertFalse(Filter::match(Filter::like('foo', 'bar'), []));
        $this->assertTrue(Filter::match(Filter::unequal('bar', 'foo'), []));
        $this->assertTrue(Filter::match(Filter::unlike('bar', 'foo'), []));
        $this->assertFalse(Filter::match(Filter::greaterThan('foo', 123), []));
        $this->assertFalse(Filter::match(Filter::lessThan('foo', 123), []));
        $this->assertFalse(Filter::match(Filter::lessThanOrEqual('foo', 123), []));
        $this->assertFalse(Filter::match(Filter::greaterThanOrEqual('foo', 123), []));
    }
}
