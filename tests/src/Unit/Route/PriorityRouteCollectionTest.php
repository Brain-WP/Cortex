<?php
/*
 * This file is part of the cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Tests\Unit\Route;

use Andrew\Proxy;
use Brain\Cortex\Route\PriorityRouteCollection;
use Brain\Cortex\Route\Route;
use Brain\Cortex\Route\RouteInterface;
use Brain\Cortex\Tests\TestCase;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class PriorityRouteCollectionTest extends TestCase
{

    public function testAddRoute()
    {
        $route1 = new Route(['id' => 'r_1']);
        $route2 = new Route(['id' => 'r_2', 'priority' => 11]);
        $route3 = new Route(['id' => 'r_3', 'priority' => 5]);
        $route4 = new Route(['id' => 'r_4', 'priority' => 9]);

        $collection = new PriorityRouteCollection();

        $collection
            ->addRoute($route1)
            ->addRoute($route2)
            ->addRoute($route3)
            ->addRoute($route4);

        $proxy = new Proxy($collection);

        assertSame(4, count($collection));
        /** @noinspection PhpUndefinedFieldInspection */
        assertSame([10, 11, 5, 9], $proxy->priorities);

        $actual = [];
        /** @var Route $route */
        foreach ($collection as $route) {
            $actual[] = $route->id();
        }

        assertSame(['r_3', 'r_4', 'r_1', 'r_2'], $actual);
    }

    public function testPagedNotAddedIfNoPath()
    {
        $route = new Route(['id' => 'route_example', 'paged' => RouteInterface::PAGED_SINGLE]);

        $collection = new PriorityRouteCollection();
        $collection->addRoute($route);
        $proxy = new Proxy($collection);

        assertSame(1, count($collection));
        /** @noinspection PhpUndefinedFieldInspection */
        assertSame([10], $proxy->priorities);
    }

    public function testPagedSingle()
    {
        $route = new Route([
            'id'    => 'route_example',
            'path'  => '/foo',
            'paged' => RouteInterface::PAGED_SINGLE
        ]);

        $collection = new PriorityRouteCollection();
        $collection->addRoute($route);
        $proxy = new Proxy($collection);

        assertSame(2, count($collection));
        /** @noinspection PhpUndefinedFieldInspection */
        assertSame([10, 11], $proxy->priorities);

        /** @var Route $route */
        $i = 0;
        foreach ($collection as $route) {
            $id = $i === 0 ? 'route_example_paged' : 'route_example';
            $path = $i === 0 ? '/foo/{page:\d+}' : '/foo';
            assertSame($id, $route->id());
            assertSame($path, $route['path']);
            $i++;
        }
    }

    public function testPagedArchive()
    {
        $route = new Route([
            'id'       => 'route_example',
            'path'     => '/bar',
            'priority' => 32,
            'paged'    => RouteInterface::PAGED_ARCHIVE
        ]);

        $collection = new PriorityRouteCollection();
        $collection->addRoute($route);
        $proxy = new Proxy($collection);

        assertSame(2, count($collection));
        /** @noinspection PhpUndefinedFieldInspection */
        assertSame([32, 33], $proxy->priorities);

        /** @var Route $route */
        $i = 0;
        foreach ($collection as $route) {
            $id = $i === 0 ? 'route_example_paged' : 'route_example';
            $path = $i === 0 ? '/bar/page/{paged:\d+}' : '/bar';
            assertSame($id, $route->id());
            assertSame($path, $route['path']);
            $i++;
        }
    }

}