<?php

namespace ipl\Tests\Stdlib;

use ipl\Stdlib\Contract\Filterable;
use ipl\Stdlib\Filter;
use ipl\Tests\Stdlib\FiltersTest\FiltersUser;

class FiltersTest extends \PHPUnit\Framework\TestCase
{
    public function testFilterKeepsCurrentHierarchy()
    {
        $filterable = new FiltersUser();
        $filterable->filter(Filter::equal('', ''));
        $filterable->filter(Filter::unequal('', ''));

        $this->assertSameFilterHierarchy($filterable, Filter::all(
            Filter::equal('', ''),
            Filter::unequal('', '')
        ));
    }

    public function testFilterWrapsCurrentHierarchy()
    {
        $filterable = new FiltersUser();
        $filterable->orFilter(Filter::equal('', ''));
        $filterable->filter(Filter::unequal('', ''));

        $this->assertSameFilterHierarchy($filterable, Filter::all(
            Filter::any(Filter::equal('', '')),
            Filter::unequal('', '')
        ));
    }

    public function testOrFilterKeepsCurrentHierarchy()
    {
        $filterable = new FiltersUser();
        $filterable->orFilter(Filter::equal('', ''));
        $filterable->orFilter(Filter::unequal('', ''));

        $this->assertSameFilterHierarchy($filterable, Filter::any(
            Filter::equal('', ''),
            Filter::unequal('', '')
        ));
    }

    public function testOrFilterWrapsCurrentHierarchy()
    {
        $filterable = new FiltersUser();
        $filterable->filter(Filter::equal('', ''));
        $filterable->orFilter(Filter::unequal('', ''));

        $this->assertSameFilterHierarchy($filterable, Filter::any(
            Filter::all(Filter::equal('', '')),
            Filter::unequal('', '')
        ));
    }

    public function testNotFilterKeepsCurrentHierarchy()
    {
        $filterable = new FiltersUser();
        $filterable->notFilter(Filter::equal('', ''));
        $filterable->notFilter(Filter::unequal('', ''));

        $this->assertSameFilterHierarchy($filterable, Filter::all(
            Filter::none(Filter::equal('', '')),
            Filter::none(Filter::unequal('', ''))
        ));
    }

    public function testNotFilterWrapsCurrentHierarchy()
    {
        $filterable = new FiltersUser();
        $filterable->orFilter(Filter::equal('', ''));
        $filterable->notFilter(Filter::unequal('', ''));

        $this->assertSameFilterHierarchy($filterable, Filter::all(
            Filter::any(Filter::equal('', '')),
            Filter::none(Filter::unequal('', ''))
        ));
    }

    public function testOrNotFilterKeepsCurrentHierarchy()
    {
        $filterable = new FiltersUser();
        $filterable->orNotFilter(Filter::equal('', ''));
        $filterable->orNotFilter(Filter::unequal('', ''));

        $this->assertSameFilterHierarchy($filterable, Filter::any(
            Filter::none(Filter::equal('', '')),
            Filter::none(Filter::unequal('', ''))
        ));
    }

    public function testOrNotFilterWrapsCurrentHierarchy()
    {
        $filterable = new FiltersUser();
        $filterable->filter(Filter::equal('', ''));
        $filterable->orNotFilter(Filter::unequal('', ''));

        $this->assertSameFilterHierarchy($filterable, Filter::any(
            Filter::all(Filter::equal('', '')),
            Filter::none(Filter::unequal('', ''))
        ));
    }

    protected function assertSameFilterHierarchy(Filterable $filterable, Filter\Chain $expected)
    {
        $actual = $filterable->getFilter();

        $checkHierarchy = function ($expected, $actual) use (&$checkHierarchy) {
            $expectedArray = iterator_to_array($expected);
            $actualArray = iterator_to_array($actual);
            foreach ($expectedArray as $key => $rule) {
                $this->assertTrue(isset($actualArray[$key]));
                $this->assertInstanceOf(get_class($rule), $actualArray[$key]);
                if ($rule instanceof Filter\Chain) {
                    $checkHierarchy($rule, $actualArray[$key]);
                }
            }
        };

        $checkHierarchy($expected, $actual);
    }
}
