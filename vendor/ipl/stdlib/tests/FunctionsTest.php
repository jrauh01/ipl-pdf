<?php

namespace ipl\Tests\Stdlib;

use ArrayIterator;
use EmptyIterator;
use InvalidArgumentException;
use ipl\Stdlib;
use stdClass;

class FunctionsTest extends TestCase
{
    protected const YIELD_GROUPS_DATA = [
        'one'   => [
            'group' => 1,
            'id'    => 1
        ],
        'two'   => [
            'group' => 1,
            'id'    => 2
        ],
        'three' => [
            'group' => 1,
            'id'    => 3
        ],
        'four'  => [
            'group' => 2,
            'id'    => 4
        ],
        'five'  => [
            'group' => 3,
            'id'    => 5
        ],
        'six'   => [
            'group' => 3,
            'id'    => 6
        ],
        'seven' => [
            'group' => 3,
            'id'    => 7
        ]
    ];

    public function testGetPhpTypeWithObject()
    {
        $object = (object) [];

        $this->assertSame('stdClass', Stdlib\get_php_type($object));
    }

    public function testGetPhpTypeWithInstance()
    {
        $instance = new stdClass();

        $this->assertSame('stdClass', Stdlib\get_php_type($instance));
    }

    public function testGetPhpTypeWithPhpType()
    {
        $array = [];

        $this->assertSame('array', Stdlib\get_php_type($array));
    }

    public function testArrayvalWithArray()
    {
        $array = ['key' => 'value'];

        $this->assertSame($array, Stdlib\arrayval($array));
    }

    public function testArrayvalWithObject()
    {
        $array = ['key' => 'value'];

        $object = (object) $array;

        $this->assertSame($array, Stdlib\arrayval($object));
    }

    public function testArrayvalWithTraversable()
    {
        $array = ['key' => 'value'];

        $traversable = new ArrayIterator($array);

        $this->assertSame($array, Stdlib\arrayval($traversable));
    }

    public function testArrayvalException()
    {
        $this->expectException(InvalidArgumentException::class);

        Stdlib\arrayval(null);
    }

    public function testIterableKeyFirstReturnsFirstKeyIfIterableImplementsIteratorAndIsNotEmpty()
    {
        $this->assertSame('a', Stdlib\iterable_key_first(new ArrayIterator(['a' => 'a', 'b' => 'b'])));
    }

    public function testIterableKeyFirstReturnsFirstKeyIfIterableIsArrayAndIsNotEmpty()
    {
        $this->assertSame('a', Stdlib\iterable_key_first(['a' => 'a', 'b' => 'b']));
    }

    public function testIterableKeyFirstReturnsFirstKeyIfIterableIsGeneratorAndIsNotEmpty()
    {
        $this->assertSame('a', Stdlib\iterable_key_first(call_user_func(function () {
            yield 'a' => 'a';
            yield 'b' => 'b';
        })));
    }

    public function testIterableKeyFirstReturnsNullIfIterableImplementsIteratorAndIsEmpty()
    {
        $this->assertNull(Stdlib\iterable_key_first(new ArrayIterator([])));
    }

    public function testIterableKeyFirstReturnsNullIfIterableIsArrayAndIsEmpty()
    {
        $this->assertNull(Stdlib\iterable_key_first([]));
    }

    public function testIterableKeyFirstReturnsNullIfIterableIsGeneratorAndIsEmpty()
    {
        $this->assertNull(Stdlib\iterable_key_first(call_user_func(function () {
            return;
            /** @noinspection PhpUnreachableStatementInspection Empty generator */
            yield;
        })));
    }

    public function testYieldGroupsWithEmptyIterator()
    {
        $this->assertEquals([], iterator_to_array(Stdlib\yield_groups(new EmptyIterator(), function () {
        })));
    }

    public function testYieldGroupsCallbackArguments()
    {
        iterator_to_array(
            Stdlib\yield_groups(new ArrayIterator(static::YIELD_GROUPS_DATA), function (array $v, string $k): int {
                $this->assertSame(static::YIELD_GROUPS_DATA[$k], $v);

                return $v['group'];
            })
        );
    }

    public function testYieldGroupsWithCallbackReturningCriterion()
    {
        $this->assertEquals(
            [
                1 => [
                    'one'   => [
                        'group' => 1,
                        'id'    => 1
                    ],
                    'two'   => [
                        'group' => 1,
                        'id'    => 2
                    ],
                    'three' => [
                        'group' => 1,
                        'id'    => 3
                    ]
                ],
                2 => [
                    'four' => [
                        'group' => 2,
                        'id'    => 4
                    ]
                ],
                3 => [
                    'five'  => [
                        'group' => 3,
                        'id'    => 5
                    ],
                    'six'   => [
                        'group' => 3,
                        'id'    => 6
                    ],
                    'seven' => [
                        'group' => 3,
                        'id'    => 7
                    ]
                ]
            ],
            iterator_to_array(
                Stdlib\yield_groups(new ArrayIterator(static::YIELD_GROUPS_DATA), function (array $v): int {
                    return $v['group'];
                })
            )
        );
    }

    public function testYieldGroupsWithCallbackReturningCriterionAndValue()
    {
        $this->assertEquals(
            [
                1 => [
                    'one'   => 1,
                    'two'   => 2,
                    'three' => 3
                ],
                2 => [
                    'four' => 4,
                ],
                3 => [
                    'five'  => 5,
                    'six'   => 6,
                    'seven' => 7
                ]
            ],
            iterator_to_array(
                Stdlib\yield_groups(new ArrayIterator(static::YIELD_GROUPS_DATA), function (array $v): array {
                    return [$v['group'], $v['id']];
                })
            )
        );
    }

    public function testYieldGroupsWithCallbackReturningCriterionValueAndKey()
    {
        $this->assertEquals(
            [
                1 => [
                    1 => 1,
                    2 => 2,
                    3 => 3
                ],
                2 => [
                    4 => 4
                ],
                3 => [
                    5 => 5,
                    6 => 6,
                    7 => 7
                ]
            ],
            iterator_to_array(
                Stdlib\yield_groups(new ArrayIterator(static::YIELD_GROUPS_DATA), function (array $v): array {
                    return [$v['group'], $v['id'], $v['id']];
                })
            )
        );
    }

    public function testIterableValueFirstReturnsFirstValueIfIterableImplementsIteratorAndIsNotEmpty()
    {
        $this->assertSame('a', Stdlib\iterable_value_first(new ArrayIterator(['a', 'b'])));
    }

    public function testIterableValueFirstReturnsFirstValueIfIterableIsArrayAndIsNotEmpty()
    {
        $this->assertSame('a', Stdlib\iterable_value_first(['a', 'b']));
    }

    public function testIterableValueFirstReturnsFirstValueIfIterableIsGeneratorAndIsNotEmpty()
    {
        $this->assertSame('a', Stdlib\iterable_value_first(call_user_func(function () {
            yield 'a';
            yield 'b';
        })));
    }

    public function testIterableValueFirstReturnsNullIfIterableImplementsIteratorAndIsEmpty()
    {
        $this->assertNull(Stdlib\iterable_value_first(new ArrayIterator([])));
    }

    public function testIterableValueFirstReturnsNullIfIterableIsArrayAndIsEmpty()
    {
        $this->assertNull(Stdlib\iterable_value_first([]));
    }

    public function testIterableValueFirstReturnsNullIfIterableIsGeneratorAndIsEmpty()
    {
        $this->assertNull(Stdlib\iterable_value_first(call_user_func(function () {
            return;
            /** @noinspection PhpUnreachableStatementInspection Empty generator */
            yield;
        })));
    }
}
