<?php
/*
 * This file is part of the cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Tests\Unit\Group;

use Andrew\Proxy;
use Brain\Cortex\Group\Group;
use Brain\Cortex\Group\GroupCollection;
use Brain\Cortex\Group\GroupInterface;
use Brain\Cortex\Route\QueryRoute;
use Brain\Cortex\Route\Route;
use Brain\Cortex\Tests\TestCase;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class GroupCollectionTest extends TestCase
{
    public function testAddGroup()
    {
        $group = \Mockery::mock(GroupInterface::class);
        $group->shouldReceive('id')->andReturn('foo');

        $collection = new GroupCollection();
        $return = $collection->addGroup($group);

        $proxy = new Proxy($collection);

        /** @noinspection PhpUndefinedFieldInspection */
        static::assertSame($proxy->groups, ['foo' => $group]);
        static::assertSame($return, $collection);
    }

    public function testMergeGroup()
    {
        $route = new Route([
            'id'    => 'my_route',
            'group' => 'test_group',
            'vars'  => [
                'foo' => 'bar',
            ],
        ]);

        $group = new Group([
            'id'      => 'test_group',
            'handler' => 'my_handler',
            'host'    => 'example.com',
            'vars'    => [
                'meh' => 'meh',
            ],
        ]);

        $expected = [
            'id'      => 'my_route',
            'group'   => 'test_group',
            'vars'    => [
                'foo' => 'bar',
            ],
            'handler' => 'my_handler',
            'host'    => 'example.com',
        ];

        $collection = new GroupCollection();
        $collection->addGroup($group);

        /** @var \Brain\Cortex\Route\RouteInterface $newRoute */
        $newRoute = $collection->mergeGroup($route);
        $actual = array_filter($newRoute->toArray());

        ksort($actual);
        ksort($expected);

        static::assertSame($expected, $actual);
    }

    public function testMergeMultipleGroups()
    {
        $route = new QueryRoute(
            '/path/to',
            ['name' => 'test'],
            [
                'id'    => 'my_route',
                'paged' => true,
                'group' => ['group_1', 'group_2', 'group_3']
            ]
        );

        $handler = $route->offsetGet('handler');

        $group1 = new Group([
            'id'      => 'group_1',
            'handler' => 'my_handler',
            'method'  => 'POST',
            'vars'    => ['foo' => 'bar'],
        ]);

        $group2 = new Group([
            'id'                 => 'group_2',
            'paged'              => false,
            'merge_query_string' => true,
        ]);

        $expected = [
            'id'                 => 'my_route',
            'path'               => '/path/to',
            'vars'               => ['name' => 'test'],
            'paged'              => true,
            'group'              => ['group_1', 'group_2', 'group_3'],
            'handler'            => $handler,
            'method'             => 'POST',
            'merge_query_string' => true,
        ];

        $collection = new GroupCollection();
        $collection->addGroup($group1)->addGroup($group2);
        
        /** @var \Brain\Cortex\Route\RouteInterface $newRoute */
        $newRoute = $collection->mergeGroup($route);
        $actual = array_filter($newRoute->toArray());

        ksort($actual);
        ksort($expected);

        static::assertSame($expected, $actual);
    }
}
