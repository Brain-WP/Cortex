<?php
/*
 * This file is part of the cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Tests\Unit\Router;

use Brain\Cortex\Route\PriorityRouteCollection;
use Brain\Cortex\Route\Route;
use Brain\Cortex\Router\RouteFilterIterator;
use Brain\Cortex\Tests\TestCase;
use Brain\Cortex\Uri\UriInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class RouteFilterIteratorTest extends TestCase
{
    public function testAcceptForScheme()
    {
        $collection = new PriorityRouteCollection();

        $route1 = new Route(['id' => 'r1', 'scheme' => 'http']);
        $route2 = new Route(['id' => 'r2', 'scheme' => 'https']);
        $route3 = new Route(['id' => 'r3']);
        $route4 = new Route(['id' => 'r4', 'scheme' => 'ftp']);

        $collection->addRoute($route1)->addRoute($route2)->addRoute($route3)->addRoute($route4);

        $uri = \Mockery::mock(UriInterface::class);
        $uri->shouldReceive('scheme')->andReturn('http');
        $uri->shouldReceive('host')->andReturn('www.example.com');

        $ok = [];
        $filter = new RouteFilterIterator($collection, $uri);
        /** @var \Brain\Cortex\Route\Route $route */
        foreach ($filter as $route) {
            $ok[] = $route->id();
        }

        assertSame(['r1', 'r3'], $ok);
    }

    public function testAcceptForHost()
    {
        $collection = new PriorityRouteCollection();

        $route1 = new Route(['id' => 'r1', 'host' => 'www.example.com']);
        $route2 = new Route(['id' => 'r2', 'host' => 'www.example.it']);
        $route3 = new Route(['id' => 'r3']);
        $route4 = new Route(['id' => 'r4', 'host' => 'example.com']);

        $collection->addRoute($route1)->addRoute($route2)->addRoute($route3)->addRoute($route4);

        $uri = \Mockery::mock(UriInterface::class);
        $uri->shouldReceive('scheme')->andReturn('http');
        $uri->shouldReceive('host')->andReturn('www.example.com');

        $ok = [];
        $filter = new RouteFilterIterator($collection, $uri);
        /** @var \Brain\Cortex\Route\Route $route */
        foreach ($filter as $route) {
            $ok[] = $route->id();
        }

        assertSame(['r1', 'r3'], $ok);
    }

    public function testAcceptForHostWildcard()
    {
        $collection = new PriorityRouteCollection();

        $route1 = new Route(['id' => 'r1', 'host' => 'www.example.*']);
        $route2 = new Route(['id' => 'r2', 'host' => '*.example.*']);
        $route3 = new Route(['id' => 'r3']);
        $route4 = new Route(['id' => 'r4', 'host' => 'example.*']);

        $collection->addRoute($route1)->addRoute($route2)->addRoute($route3)->addRoute($route4);

        $uri = \Mockery::mock(UriInterface::class);
        $uri->shouldReceive('scheme')->andReturn('http');
        $uri->shouldReceive('host')->andReturn('www.example.com');

        $ok = [];
        $filter = new RouteFilterIterator($collection, $uri);
        /** @var \Brain\Cortex\Route\Route $route */
        foreach ($filter as $route) {
            $ok[] = $route->id();
        }

        assertSame(['r1', 'r2', 'r3'], $ok);
    }
}
