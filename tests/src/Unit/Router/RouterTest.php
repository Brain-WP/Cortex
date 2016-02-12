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

use Andrew\Proxy;
use Brain\Cortex\Group\GroupCollectionInterface;
use Brain\Cortex\Route\PriorityRouteCollection;
use Brain\Cortex\Route\Route;
use Brain\Cortex\Route\RouteCollectionInterface;
use Brain\Cortex\Router\MatchingResult;
use Brain\Cortex\Router\Router;
use Brain\Cortex\Tests\TestCase;
use Brain\Cortex\Uri\UriInterface;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class RouterTest extends TestCase
{

    public function testMatchNothingIfAlreadyMatched()
    {
        $routes = \Mockery::mock(RouteCollectionInterface::class);
        $groups = \Mockery::mock(GroupCollectionInterface::class);
        $uri = \Mockery::mock(UriInterface::class);

        $router = new Router($routes, $groups);

        $result = \Mockery::mock(MatchingResult::class);

        $proxy = new Proxy($router);
        /** @noinspection PhpUndefinedFieldInspection */
        $proxy->results = $result;

        assertSame($result, $router->match($uri, 'GET'));
    }

    public function testMatchNothingIfNoRoutes()
    {
        $routes = new PriorityRouteCollection();
        $groups = \Mockery::mock(GroupCollectionInterface::class);
        $uri = \Mockery::mock(UriInterface::class);

        $router = new Router($routes, $groups);
        $result = $router->match($uri, 'GET');

        $expected = [
            'route'    => null,
            'path'     => null,
            'vars'     => null,
            'handler'  => null,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        assertFalse($result->matched());
        assertSame($expected, $data);
    }

    public function testMatchNothingIfNoFilteredRoutes()
    {
        $routes = new PriorityRouteCollection();
        $routes->addRoute(new Route(['scheme' => 'https']));

        $groups = \Mockery::mock(GroupCollectionInterface::class);

        $uri = \Mockery::mock(UriInterface::class);
        $uri->shouldReceive('scheme')->andReturn('http');
        $uri->shouldReceive('host')->andReturn('example.com');

        $router = new Router($routes, $groups);
        $result = $router->match($uri, 'GET');

        $expected = [
            'route'    => null,
            'path'     => null,
            'vars'     => null,
            'handler'  => null,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        assertFalse($result->matched());
        assertSame($expected, $data);
    }

    public function testMatchNothingIfNoValidatingRoutes()
    {
        $route = new Route([]);
        $routes = new PriorityRouteCollection();

        $routes->addRoute($route);

        $groups = \Mockery::mock(GroupCollectionInterface::class);
        $groups->shouldReceive('mergeGroup')->once()->with($route)->andReturn($route);

        $uri = \Mockery::mock(UriInterface::class);
        $uri->shouldReceive('scheme')->andReturn('http');
        $uri->shouldReceive('host')->andReturn('example.com');

        $router = new Router($routes, $groups);
        $result = $router->match($uri, 'GET');

        $expected = [
            'route'    => null,
            'path'     => null,
            'vars'     => null,
            'handler'  => null,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        assertFalse($result->matched());
        assertSame($expected, $data);
    }

    public function testMatchNotMatching()
    {
        $route = new Route([
            'id'      => 'r1',
            'path'    => '/foo',
            'handler' => function () {
                return func_get_args();
            },
            'method'  => 'POST'
        ]);
        $routes = new PriorityRouteCollection();

        $routes->addRoute($route);

        $groups = \Mockery::mock(GroupCollectionInterface::class);
        $groups->shouldReceive('mergeGroup')->once()->with($route)->andReturn($route);

        $uri = \Mockery::mock(UriInterface::class);
        $uri->shouldReceive('scheme')->andReturn('http');
        $uri->shouldReceive('host')->andReturn('example.com');
        $uri->shouldReceive('path')->andReturn('bar');

        $collector = \Mockery::mock(RouteCollector::class);
        $collector->shouldReceive('addRoute')->once()->with('POST', '/foo', 'r1')->andReturnNull();
        $collector->shouldReceive('getData')->once()->andReturn(['foo' => 'bar']);

        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->with('POST', '/bar')->andReturn([0]);

        $factory = function (array $args) use ($dispatcher) {
            assertSame($args, ['foo' => 'bar']);

            return $dispatcher;
        };

        $router = new Router($routes, $groups, $collector, $factory);
        $result = $router->match($uri, 'POST');

        $expected = [
            'route'    => null,
            'path'     => null,
            'vars'     => null,
            'handler'  => null,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        assertFalse($result->matched());
        assertSame($expected, $data);
    }

    public function testMatchMatching()
    {
        $handler = function () {
            return func_get_args();
        };

        $route = new Route([
            'id'      => 'r1',
            'path'    => '/foo',
            'handler' => $handler,
            'vars'    => ['d' => 'D'],
            'method'  => 'POST'
        ]);
        $routes = new PriorityRouteCollection();

        $routes->addRoute($route);

        $groups = \Mockery::mock(GroupCollectionInterface::class);
        $groups->shouldReceive('mergeGroup')->once()->with($route)->andReturn($route);

        $uri = \Mockery::mock(UriInterface::class);
        $uri->shouldReceive('scheme')->andReturn('http');
        $uri->shouldReceive('host')->andReturn('example.com');
        $uri->shouldReceive('path')->andReturn('foo');
        $uri->shouldReceive('vars')->once()->andReturn(['c' => 'C']);

        $collector = \Mockery::mock(RouteCollector::class);
        $collector->shouldReceive('addRoute')->once()->with('POST', '/foo', 'r1')->andReturnNull();
        $collector->shouldReceive('getData')->once()->andReturn(['foo' => 'bar']);

        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->with('POST', '/foo')->andReturn([
            Dispatcher::FOUND,
            'r1',
            ['a' => 'A', 'b' => 'B']
        ]);

        $factory = function (array $args) use ($dispatcher) {
            assertSame($args, ['foo' => 'bar']);

            return $dispatcher;
        };

        $router = new Router($routes, $groups, $collector, $factory);
        $result = $router->match($uri, 'POST');

        $expected = [
            'route'    => 'r1',
            'path'     => '/foo',
            'vars'     => ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'],
            'handler'  => $handler,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        assertTrue($result->matched());
        assertSame($expected, $data);
    }

    public function testMatchMatchingNoQueryVars()
    {
        $handler = function () {
            return func_get_args();
        };

        $route = new Route([
            'id'                 => 'r1',
            'path'               => '/foo',
            'handler'            => $handler,
            'vars'               => ['d' => 'D'],
            'method'             => 'POST',
            'merge_query_string' => false,
        ]);
        $routes = new PriorityRouteCollection();

        $routes->addRoute($route);

        $groups = \Mockery::mock(GroupCollectionInterface::class);
        $groups->shouldReceive('mergeGroup')->once()->with($route)->andReturn($route);

        $uri = \Mockery::mock(UriInterface::class);
        $uri->shouldReceive('scheme')->andReturn('http');
        $uri->shouldReceive('host')->andReturn('example.com');
        $uri->shouldReceive('path')->andReturn('foo');

        $collector = \Mockery::mock(RouteCollector::class);
        $collector->shouldReceive('addRoute')->once()->with('POST', '/foo', 'r1')->andReturnNull();
        $collector->shouldReceive('getData')->once()->andReturn(['foo' => 'bar']);

        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->with('POST', '/foo')->andReturn([
            Dispatcher::FOUND,
            'r1',
            ['a' => 'A', 'b' => 'B']
        ]);

        $factory = function (array $args) use ($dispatcher) {
            assertSame($args, ['foo' => 'bar']);

            return $dispatcher;
        };

        $router = new Router($routes, $groups, $collector, $factory);
        $result = $router->match($uri, 'POST');

        $expected = [
            'route'    => 'r1',
            'path'     => '/foo',
            'vars'     => ['a' => 'A', 'b' => 'B', 'd' => 'D'],
            'handler'  => $handler,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        assertTrue($result->matched());
        assertSame($expected, $data);
    }

    public function testMatchMatchingCallableVars()
    {
        $handler = function () {
            return func_get_args();
        };

        $route = new Route([
            'id'      => 'r1',
            'path'    => '/foo',
            'handler' => $handler,
            'vars'    => 'array_keys',
            'method'  => 'POST'
        ]);
        $routes = new PriorityRouteCollection();

        $routes->addRoute($route);

        $groups = \Mockery::mock(GroupCollectionInterface::class);
        $groups->shouldReceive('mergeGroup')->once()->with($route)->andReturn($route);

        $uri = \Mockery::mock(UriInterface::class);
        $uri->shouldReceive('scheme')->andReturn('http');
        $uri->shouldReceive('host')->andReturn('example.com');
        $uri->shouldReceive('path')->andReturn('foo');
        $uri->shouldReceive('vars')->once()->andReturn(['c' => 'C']);

        $collector = \Mockery::mock(RouteCollector::class);
        $collector->shouldReceive('addRoute')->once()->with('POST', '/foo', 'r1')->andReturnNull();
        $collector->shouldReceive('getData')->once()->andReturn(['foo' => 'bar']);

        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->with('POST', '/foo')->andReturn([
            Dispatcher::FOUND,
            'r1',
            ['a' => 'A', 'b' => 'B']
        ]);

        $factory = function (array $args) use ($dispatcher) {
            assertSame($args, ['foo' => 'bar']);

            return $dispatcher;
        };

        $router = new Router($routes, $groups, $collector, $factory);
        $result = $router->match($uri, 'POST');

        $expected = [
            'route'    => 'r1',
            'path'     => '/foo',
            'vars'     => ['a', 'b', 'c'],
            'handler'  => $handler,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        assertTrue($result->matched());
        assertSame($expected, $data);
    }
}