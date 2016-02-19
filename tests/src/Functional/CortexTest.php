<?php
/*
 * This file is part of the cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Tests\Functional;

use Andrew\Proxy;
use Brain\Cortex;
use Brain\Cortex\Route\RouteCollectionInterface;
use Brain\Cortex\Route\QueryRoute;
use Brain\Cortex\Router\MatchingResult;
use Brain\Cortex\Tests\TestCaseFunctional;
use Brain\Cortex\Uri\WordPressUri;
use Brain\Monkey\Functions;
use Brain\Monkey\WP\Actions;
use Brain\Monkey\WP\Filters;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class CortexTest extends TestCaseFunctional
{
    public function testBootOnce()
    {
        $boot1 = Cortex::boot();
        $boot2 = Cortex::boot();

        assertTrue($boot1);
        assertFalse($boot2);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessageRegExp /before "do_parse_request"/
     */
    public function testBootFailsAfterParseRequest()
    {
        do_action('parse_request');
        Cortex::boot();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Inside do_parse_request
     */
    public function testBootCatchesExceptionDuringDoParseRequest()
    {
        Filters::expectAdded('do_parse_request')
               ->once()
               ->with(\Mockery::type('Closure'), 100, 2)
               ->whenHappen(function () {
                   throw new \Exception('Inside do_parse_request');
               });

        Cortex::boot();
    }

    public function testCortexDoNothingIfNoRoutes()
    {
        Functions::when('home_url')->justReturn('http://example.com/foo/');
        Functions::when('remove_all_filters')->justReturn();

        $request = self::buildPsrRequest('http://example.com/foo/bar/baz');

        $cortex = new Proxy(new Cortex());
        $wp = \Mockery::mock('WP');

        $do = $cortex->doBoot($wp, true, $request);

        assertTrue($do);
    }

    public function testCortexBuildUriWithNoRequest()
    {
        $cortex = new Proxy(new Cortex());

        assertInstanceOf(WordPressUri::class, $cortex->factoryUri());
    }

    public function testRouterReturnRouteResultsWhenGiven()
    {
        Functions::when('home_url')->justReturn('http://example.com/');
        Functions::when('remove_all_filters')->justReturn();

        Actions::expectFired('cortex.routes')
               ->once()
               ->whenHappen(function (RouteCollectionInterface $routes) {
                   $routes->addRoute(new QueryRoute('/bar/baz', function () {
                       return new MatchingResult([]);
                   }));
               });

        Actions::expectFired('cortex.matched')->never();

        $request = self::buildPsrRequest('http://example.com/bar/baz');

        $cortex = new Proxy(new Cortex());
        $wp = \Mockery::mock('WP');

        $do = $cortex->doBoot($wp, true, $request);

        assertTrue($do);
        assertFalse(isset($wp->query_vars));
    }

    public function testCortexMatchStaticRoute()
    {
        Functions::when('home_url')->justReturn('http://example.com/foo/');
        Functions::when('remove_all_filters')->justReturn();

        Actions::expectFired('cortex.routes')
               ->once()
               ->whenHappen(function (RouteCollectionInterface $routes) {
                   $routes->addRoute(new QueryRoute('/bar/baz', function () {
                       return ['post_type' => 'products'];
                   }));
               });

        Actions::expectFired('cortex.matched')->once();

        $request = self::buildPsrRequest('http://example.com/foo/bar/baz');

        $cortex = new Proxy(new Cortex());
        $wp = \Mockery::mock('WP');

        $do = $cortex->doBoot($wp, true, $request);

        assertSame(['post_type' => 'products'], $wp->query_vars);
        assertFalse($do);
    }

    public function testCortexMatchPagedRoute()
    {
        Functions::when('home_url')->justReturn('http://example.com/');
        Functions::when('remove_all_filters')->justReturn();

        Actions::expectFired('cortex.routes')
               ->once()
               ->whenHappen(function (RouteCollectionInterface $routes) {
                   $routes->addRoute(new QueryRoute('/bar', function ($vars) {
                       isset($vars['paged']) and $vars['paged'] = (int) $vars['paged'];

                       return $vars;
                   }, ['paged' => QueryRoute::PAGED_ARCHIVE]));
               });

        Actions::expectFired('cortex.matched')->once();

        $request = self::buildPsrRequest('http://example.com/bar/page/3');

        $cortex = new Proxy(new Cortex());
        $wp = \Mockery::mock('WP');

        $do = $cortex->doBoot($wp, true, $request);

        assertSame(['paged' => 3], $wp->query_vars);
        assertFalse($do);
    }

    public function testCortexNotMatchIfDifferentMethod()
    {
        Functions::when('home_url')->justReturn('http://example.com/foo/');
        Functions::when('remove_all_filters')->justReturn();

        Actions::expectFired('cortex.routes')
               ->once()
               ->whenHappen(function (RouteCollectionInterface $routes) {
                   $routes->addRoute(new QueryRoute('/bar/baz', function () {
                       return ['post_type' => 'products'];
                   }, ['method' => 'GET']));
               });

        Actions::expectFired('cortex.matched')->never();

        $request = self::buildPsrRequest('http://example.com/foo/bar/baz', 'POST');

        $cortex = new Proxy(new Cortex());
        $wp = \Mockery::mock('WP');

        $do = $cortex->doBoot($wp, true, $request);

        assertTrue($do);
    }

    public function testCortexMatchStaticRouteWithUrlVars()
    {
        Functions::when('home_url')->justReturn('http://example.com/foo');
        Functions::when('remove_all_filters')->justReturn();

        Actions::expectFired('cortex.routes')
               ->once()
               ->whenHappen(function (RouteCollectionInterface $routes) {
                   $routes->addRoute(
                       new QueryRoute(
                           '/bar/baz',
                           function (array $vars = []) {
                                return ['post_type' => 'products', 'posts_per_page' => $vars['num']];
                            },
                           ['merge_query_string' => 1]
                       )
                   );
               });

        Actions::expectFired('cortex.matched')->once();

        $request = self::buildPsrRequest('http://example.com/foo/bar/baz?num=12');

        $cortex = new Proxy(new Cortex());
        $wp = \Mockery::mock('WP');

        $do = $cortex->doBoot($wp, true, $request);

        assertSame(['post_type' => 'products', 'posts_per_page' => '12'], $wp->query_vars);
        assertFalse($do);
    }

    public function testCortexMatchDynamicRouteWithUrlVars()
    {
        Functions::when('home_url')->justReturn('http://example.com/foo');
        Functions::when('remove_all_filters')->justReturn();

        Actions::expectFired('cortex.routes')
               ->once()
               ->whenHappen(function (RouteCollectionInterface $routes) {
                   $routes->addRoute(
                       new QueryRoute(
                           '/{type:\w+}/baz',
                           function (array $vars = []) {
                               return ['post_type' => $vars['type'], 'posts_per_page' => $vars['num']];
                           },
                           ['merge_query_string' => 1]
                       )
                   );
               });

        Actions::expectFired('cortex.matched')->once();

        $request = self::buildPsrRequest('http://example.com/foo/products/baz?num=12');

        $cortex = new Proxy(new Cortex());
        $wp = \Mockery::mock('WP');

        $do = $cortex->doBoot($wp, true, $request);

        assertSame(['post_type' => 'products', 'posts_per_page' => '12'], $wp->query_vars);
        assertFalse($do);
    }

    public function testCortexNotMatchDynamicRouteBadRequirements()
    {
        Functions::when('home_url')->justReturn('http://example.com/foo');
        Functions::when('remove_all_filters')->justReturn();

        Actions::expectFired('cortex.routes')
               ->once()
               ->whenHappen(function (RouteCollectionInterface $routes) {
                   $routes->addRoute(
                       new QueryRoute('/{type:[a-zA-Z]+}/baz', function (array $vars = []) {
                           return ['post_type' => $vars['type']];
                       })
                   );
               });

        Actions::expectFired('cortex.matched')->never();

        $request = self::buildPsrRequest('http://example.com/foo/123/baz');

        $cortex = new Proxy(new Cortex());
        $wp = \Mockery::mock('WP');

        $do = $cortex->doBoot($wp, true, $request);

        assertFalse(isset($wp->query_vars));
        assertTrue($do);
    }

    public function testCortexMatchFirstRoute()
    {
        Functions::when('home_url')->justReturn('http://example.com/foo');
        Functions::when('remove_all_filters')->justReturn();

        Actions::expectFired('cortex.routes')
               ->once()
               ->whenHappen(function (RouteCollectionInterface $routes) {
                   $routes
                       ->addRoute(
                           new QueryRoute('/{type}/baz', function (array $vars = []) {
                               return ['post_type' => $vars['type']];
                           })
                       )->addRoute(
                           new QueryRoute('/{first}/{second}', function (array $vars = []) {
                               return $vars;
                           }, ['priority' => 5, 'merge_query_string' => false])
                       );
               });

        Actions::expectFired('cortex.matched')->once();

        $request = self::buildPsrRequest('http://example.com/foo/bar/baz/?foo=bar');

        $cortex = new Proxy(new Cortex());
        $wp = \Mockery::mock('WP');

        $do = $cortex->doBoot($wp, true, $request);

        assertSame(['first' => 'bar', 'second' => 'baz'], $wp->query_vars);
        assertFalse($do);
    }

    public function testCortexNotMatchBecauseUrlChangedViaFilter()
    {
        Functions::when('home_url')->justReturn('http://example.com/foo/');
        Functions::when('remove_all_filters')->justReturn();

        Actions::expectFired('cortex.routes')
               ->once()
               ->whenHappen(function (RouteCollectionInterface $routes) {
                   $routes->addRoute(new QueryRoute('/bar/baz', function () {
                       return ['post_type' => 'products'];
                   }));
               });

        Actions::expectFired('cortex.matched')->never();

        Filters::expectApplied('cortex.uri.instance')->once()->andReturnUsing(function () {
            $uri = \Mockery::mock(Cortex\Uri\UriInterface::class);
            $uri->shouldReceive('scheme')->andReturn('http');
            $uri->shouldReceive('host')->andReturn('example.com');
            $uri->shouldReceive('vars')->andReturn([]);
            $uri->shouldReceive('path')->andReturn('meh/meh/meh'); // this does not match
            $uri->shouldReceive('chunks')->andReturn(['meh', 'meh', 'meh']);

            return $uri;
        });

        $request = self::buildPsrRequest('http://example.com/foo/bar/baz');

        $cortex = new Proxy(new Cortex());
        $wp = \Mockery::mock('WP');

        $do = $cortex->doBoot($wp, true, $request);

        assertFalse(isset($wp->query_vars));
        assertTrue($do);
    }

    public function testCortexMatchWhenUrlChangedViaFilterIsInvalid()
    {
        Functions::when('home_url')->justReturn('http://example.com/foo/');
        Functions::when('remove_all_filters')->justReturn();

        Actions::expectFired('cortex.routes')
               ->once()
               ->whenHappen(function (RouteCollectionInterface $routes) {
                   $routes->addRoute(new QueryRoute('/bar/baz', function () {
                       return ['post_type' => 'products'];
                   }));
               });

        Filters::expectApplied('cortex.uri.instance')->once()->andReturnUsing(function () {
            return 'http://example.com/foo/bar/baz';
        });

        Actions::expectFired('cortex.matched')->once();

        $request = self::buildPsrRequest('http://example.com/foo/bar/baz');

        $cortex = new Proxy(new Cortex());
        $wp = \Mockery::mock('WP');

        $do = $cortex->doBoot($wp, true, $request);

        assertSame(['post_type' => 'products'], $wp->query_vars);
        assertFalse($do);
    }
}
