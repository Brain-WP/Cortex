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
use Brain\Monkey\Functions;
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

        static::assertSame($result, $router->match($uri, 'GET'));
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
            'matches'  => null,
            'handler'  => null,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        static::assertFalse($result->matched());
        static::assertSame($expected, $data);
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
            'matches'  => null,
            'handler'  => null,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        static::assertFalse($result->matched());
        static::assertSame($expected, $data);
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
        $uri->shouldReceive('chunks')->andReturn([]);

        $router = new Router($routes, $groups);
        $result = $router->match($uri, 'GET');

        $expected = [
            'route'    => null,
            'path'     => null,
            'vars'     => null,
            'matches'  => null,
            'handler'  => null,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        static::assertFalse($result->matched());
        static::assertSame($expected, $data);
    }

    public function testMatchNotMatching()
    {
        $route = new Route([
            'id'      => 'r1',
            'path'    => '/foo',
            'handler' => function () {
                return func_get_args();
            },
            'method'  => 'POST',
        ]);
        $routes = new PriorityRouteCollection();

        $routes->addRoute($route);

        $groups = \Mockery::mock(GroupCollectionInterface::class);
        $groups->shouldReceive('mergeGroup')->once()->with($route)->andReturn($route);

        $uri = \Mockery::mock(UriInterface::class);
        $uri->shouldReceive('scheme')->andReturn('http');
        $uri->shouldReceive('host')->andReturn('example.com');
        $uri->shouldReceive('path')->andReturn('bar');
        $uri->shouldReceive('chunks')->andReturn(['bar']);

        $collector = \Mockery::mock(RouteCollector::class);
        $collector->shouldReceive('addRoute')->once()->with('POST', '/foo', 'r1')->andReturnNull();
        $collector->shouldReceive('getData')->once()->andReturn(['foo' => 'bar']);

        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->with('POST', '/bar')->andReturn([0]);

        $factory = function (array $args) use ($dispatcher) {
            static::assertSame($args, ['foo' => 'bar']);

            return $dispatcher;
        };

        $router = new Router($routes, $groups, $collector, $factory);
        $result = $router->match($uri, 'POST');

        $expected = [
            'route'    => null,
            'path'     => null,
            'vars'     => null,
            'matches'  => null,
            'handler'  => null,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        static::assertFalse($result->matched());
        static::assertSame($expected, $data);
    }

    public function testMatchMatchingExactMatch()
    {
        $handler = function () {
            return func_get_args();
        };

        $route = new Route([
            'id'      => 'r1',
            'path'    => '/foo',
            'handler' => $handler,
            'vars'    => ['d' => 'D'],
            'method'  => 'POST',
        ]);
        $routes = new PriorityRouteCollection();

        $routes->addRoute($route);

        $groups = \Mockery::mock(GroupCollectionInterface::class);
        $groups->shouldReceive('mergeGroup')->once()->with($route)->andReturn($route);

        $uri = \Mockery::mock(UriInterface::class);
        $uri->shouldReceive('scheme')->andReturn('http');
        $uri->shouldReceive('host')->andReturn('example.com');
        $uri->shouldReceive('path')->andReturn('foo');
        $uri->shouldReceive('vars')->atLeast()->once()->andReturn(['c' => 'C']);
        $uri->shouldReceive('chunks')->andReturn(['foo']);

        $collector = \Mockery::mock(RouteCollector::class);
        $collector->shouldReceive('addRoute')->never();

        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->never();

        $factory = function (array $args) use ($dispatcher) {
            static::assertSame($args, ['foo' => 'bar']);

            return $dispatcher;
        };

        $router = new Router($routes, $groups, $collector, $factory);
        $result = $router->match($uri, 'POST');

        $expected = [
            'route'    => 'r1',
            'path'     => '/foo',
            'vars'     => ['d' => 'D', 'c' => 'C'],
            'matches'  => ['c' => 'C'],
            'handler'  => $handler,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        ksort($expected);
        ksort($data);

        static::assertTrue($result->matched());
        static::assertSame($expected, $data);
    }

    public function testMatchDynamicMatch()
    {
        $handler = function () {
            return func_get_args();
        };

        $route = new Route([
            'id'      => 'r1',
            'path'    => '/foo/{bar}',
            'handler' => $handler,
            'vars'    => ['d' => 'D'],
            'method'  => 'POST',
        ]);
        $routes = new PriorityRouteCollection();

        $routes->addRoute($route);

        $groups = \Mockery::mock(GroupCollectionInterface::class);
        $groups->shouldReceive('mergeGroup')->once()->with($route)->andReturn($route);

        $uri = \Mockery::mock(UriInterface::class);
        $uri->shouldReceive('scheme')->andReturn('http');
        $uri->shouldReceive('host')->andReturn('example.com');
        $uri->shouldReceive('path')->andReturn('foo/i-am-bar');
        $uri->shouldReceive('vars')->atLeast()->once()->andReturn(['c' => 'C']);
        $uri->shouldReceive('chunks')->andReturn(['foo', 'meh']);

        $collector = \Mockery::mock(RouteCollector::class);
        $collector->shouldReceive('addRoute')->once()->with('POST', '/foo/{bar}', 'r1');
        $collector->shouldReceive('getData')->once()->andReturn(['foo' => 'bar']);

        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
                   ->once()
                   ->with('POST', '/foo/i-am-bar')
                   ->andReturn([
                       Dispatcher::FOUND,
                       'r1',
                       ['bar' => 'i-am-bar'],
                   ]);

        $factory = function (array $args) use ($dispatcher) {
            static::assertSame($args, ['foo' => 'bar']);

            return $dispatcher;
        };

        $router = new Router($routes, $groups, $collector, $factory);
        $result = $router->match($uri, 'POST');

        $expected = [
            'route'    => 'r1',
            'path'     => '/foo/{bar}',
            'vars'     => ['d' => 'D', 'bar' => 'i-am-bar', 'c' => 'C'],
            'matches'  => ['bar' => 'i-am-bar', 'c' => 'C'],
            'handler'  => $handler,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        ksort($expected);
        ksort($data);

        static::assertTrue($result->matched());
        static::assertSame($expected, $data);
    }

    public function testMatchMatchingExactMatchNoQueryVars()
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
        $uri->shouldReceive('chunks')->andReturn(['foo']);
        $uri->shouldReceive('vars')->andReturn(['foo' => 'no-way']);

        $collector = \Mockery::mock(RouteCollector::class);
        $collector->shouldReceive('addRoute')->never();

        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->never();

        $factory = function (array $args) use ($dispatcher) {
            static::assertSame($args, ['foo' => 'bar']);

            return $dispatcher;
        };

        $router = new Router($routes, $groups, $collector, $factory);
        $result = $router->match($uri, 'POST');

        $expected = [
            'route'    => 'r1',
            'path'     => '/foo',
            'vars'     => ['d' => 'D'],
            'matches'  => [],
            'handler'  => $handler,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        static::assertTrue($result->matched());
        static::assertSame($expected, $data);
    }

    public function testMatchMatchingNoQueryVarsMaintainPreviewVar()
    {
        Functions::when('is_user_logged_in')->justReturn(true);

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
        $uri->shouldReceive('chunks')->andReturn(['foo']);
        $uri->shouldReceive('vars')->andReturn([
            'foo'           => 'no-way',
            'preview'       => 'true',
            'preview_id'    => '123',
            'preview_nonce' => 'abc',
        ]);

        $collector = \Mockery::mock(RouteCollector::class);
        $collector->shouldReceive('addRoute')->never();

        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->never();

        $factory = function (array $args) use ($dispatcher) {
            static::assertSame($args, ['foo' => 'bar']);

            return $dispatcher;
        };

        $router = new Router($routes, $groups, $collector, $factory);
        $result = $router->match($uri, 'POST');

        $expected = [
            'route'    => 'r1',
            'path'     => '/foo',
            'vars'     => [
                'd'             => 'D',
                'preview'       => 'true',
                'preview_id'    => '123',
                'preview_nonce' => 'abc',
            ],
            'matches'  => [],
            'handler'  => $handler,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        ksort($data);
        ksort($expected);

        static::assertTrue($result->matched());
        static::assertSame($expected, $data);
    }

    public function testMatchMatchingExactMatchCallableVars()
    {
        $handler = function () {
            return func_get_args();
        };

        $route = new Route([
            'id'      => 'r1',
            'path'    => '/foo',
            'handler' => $handler,
            'vars'    => function (array $vars) {
                return array_keys($vars);
            },
            'method'  => 'POST',
        ]);
        $routes = new PriorityRouteCollection();

        $routes->addRoute($route);

        $groups = \Mockery::mock(GroupCollectionInterface::class);
        $groups->shouldReceive('mergeGroup')->once()->with($route)->andReturn($route);

        $uri = \Mockery::mock(UriInterface::class);
        $uri->shouldReceive('scheme')->andReturn('http');
        $uri->shouldReceive('host')->andReturn('example.com');
        $uri->shouldReceive('path')->andReturn('foo');
        $uri->shouldReceive('vars')->atLeast()->once()->andReturn(['c' => 'C']);
        $uri->shouldReceive('chunks')->andReturn(['foo']);

        $collector = \Mockery::mock(RouteCollector::class);
        $collector->shouldReceive('addRoute')->never();

        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->never();

        $factory = function (array $args) use ($dispatcher) {
            static::assertSame($args, ['foo' => 'bar']);

            return $dispatcher;
        };

        $router = new Router($routes, $groups, $collector, $factory);
        $result = $router->match($uri, 'POST');

        $expected = [
            'route'    => 'r1',
            'path'     => '/foo',
            'vars'     => ['c'],
            'matches'  => ['c' => 'C'],
            'handler'  => $handler,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $proxy = new Proxy($result);
        /** @noinspection PhpUndefinedFieldInspection */
        $data = $proxy->data;

        static::assertTrue($result->matched());
        static::assertSame($expected, $data);
    }
}
