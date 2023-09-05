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
use Andrew\StaticProxy;
use Brain\Cortex;
use Brain\Cortex\Route\RouteCollectionInterface;
use Brain\Cortex\Group\GroupCollectionInterface;
use Brain\Cortex\Controller\RedirectController;
use Brain\Cortex\Tests\TestCaseFunctional;
use Brain\Monkey\Functions;
use Brain\Monkey\WP\Actions;
use Brain\Routes;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class RoutesTest extends TestCaseFunctional
{
    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessageRegExp /before "do_parse_request"/
     */
    public function testAddFailsIfDidParseRequest()
    {
        do_action('parse_request');
        Routes::add('foo', [], []);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessageRegExp /before "do_parse_request"/
     */
    public function testAddFailsIfLate()
    {
        $cortex = new StaticProxy(Cortex::class);
        /** @noinspection PhpUndefinedFieldInspection */
        $cortex->late = true;

        Routes::add('foo', [], []);
    }

    public function testAddRoutesAndGroups()
    {
        Functions::when('home_url')->justReturn('http://example.com/');
        Functions::when('remove_all_filters')->justReturn();

        $routeAdders = $groupAdders = [];

        Actions::expectAdded('cortex.routes')->twice()->whenHappen(function ($add) use (
            &$routeAdders
        ) {
            $routeAdders[] = $add;
        });

        Actions::expectAdded('cortex.groups')->once()->whenHappen(function ($add) use (&$groupAdders
        ) {
            $groupAdders[] = $add;
        });

        Actions::expectFired('cortex.routes')
               ->once()
               ->whenHappen(function (RouteCollectionInterface $routes) use (&$routeAdders) {
                   foreach ($routeAdders as $routeAdder) {
                       $routeAdder($routes);
                   }
               });

        Actions::expectFired('cortex.groups')
               ->once()
               ->whenHappen(function (GroupCollectionInterface $groups) use (&$groupAdders) {
                   foreach ($groupAdders as $groupAdder) {
                       $groupAdder($groups);
                   }
               });

        Routes::add('/foo', ['name' => 'foo'], ['id' => 'r1']);
        Routes::add('/baz', ['name' => 'baz'], ['id' => 'r2', 'group' => 'g1']);
        Routes::group('g1', ['merge_query_string' => false]);

        $request = self::buildPsrRequest('http://example.com/baz?foo=bar');
        $cortex = new Proxy(new Cortex());
        $wp = \Mockery::mock('WP');
        $do = $cortex->doBoot($wp, true, $request);

        static::assertFalse($do);
        static::assertSame(['name' => 'baz'], $wp->query_vars);
    }

    public function testMatchRedirectRoute()
    {
        Functions::when('home_url')->justReturn('http://example.com/');

        /** @var callable|null $factory */
        $factory = null;

        Actions::expectAdded('cortex.routes')->once()->whenHappen(function ($cb) use (&$factory) {
            $factory = $cb;
        });

        Actions::expectFired('cortex.routes')->once()->whenHappen(function ($routes) use (&$factory
        ) {
            $factory($routes);
        });

        Functions::expect('wp_redirect')->once()->with('https://www.google.com', 305);
        Actions::expectAdded('cortex.exit.redirect')->once()
               ->with([RedirectController::class, 'doExit'], 100);
        Actions::expectFired('cortex.exit.redirect')->once();

        Routes::redirect('/from', 'https://www.google.com', 305, true);

        $request = self::buildPsrRequest('http://example.com/from');
        $cortex = new Proxy(new Cortex());
        $do = $cortex->doBoot(\Mockery::mock('WP'), true, $request);

        static::assertTrue($do);
    }
}
