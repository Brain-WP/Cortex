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

use Brain\Cortex\Route\RedirectRoute;
use Brain\Cortex\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class RedirectRouteTest extends TestCase
{
    public function testAutoAbsoluteRedirectToFromString()
    {
        Functions::when('home_url')->alias(function ($arg = '') {
            return 'http://example.com/'.ltrim($arg, '/');
        });

        $route = new RedirectRoute('/path/from', '/meh', ['priority' => 2]);

        $expectedVars = [
            'redirect_status'   => 301,
            'redirect_external' => false,
            'redirect_to'       => 'http://example.com/meh',
        ];

        assertSame('/path/from', $route['path']);
        assertSame($expectedVars, $route['vars']);
        assertSame(2, $route['priority']);
    }

    public function testRedirectToInternalFromAbsoluteString()
    {
        $route = new RedirectRoute('/path/from', 'https://foo.bar/', ['redirect_external' => 0]);

        $expectedVars = [
            'redirect_status'   => 301,
            'redirect_external' => false,
            'redirect_to'       => 'https://foo.bar/',
        ];

        assertSame('/path/from', $route['path']);
        assertSame($expectedVars, $route['vars']);
    }

    public function testRedirectToNullIfBadStringExternal()
    {
        $route = new RedirectRoute(
            '/from',
            '/meh',
            ['redirect_status' => 307, 'redirect_external' => 1]
        );

        $expectedVars = [
            'redirect_status'   => 307,
            'redirect_external' => true,
            'redirect_to'       => '',
        ];

        assertSame('/from', $route['path']);
        assertSame($expectedVars, $route['vars']);
    }

    public function testRedirectToExternalFromString()
    {
        $route = new RedirectRoute('/path/from', 'https://foo.bar/', ['redirect_external' => 1]);

        $expectedVars = [
            'redirect_status'   => 301,
            'redirect_external' => true,
            'redirect_to'       => 'https://foo.bar/',
        ];

        assertSame('/path/from', $route['path']);
        assertSame($expectedVars, $route['vars']);
    }

    public function testAutoAbsoluteRedirectToFromCallback()
    {
        Functions::when('home_url')->alias(function ($arg = '') {
            return 'http://example.com/'.ltrim($arg, '/');
        });

        $route = new RedirectRoute(
            '/path/from',
            function (array $args) {
                return $args['foo'];
            },
            ['priority' => 2]
        );

        $expectedVars = [
            'redirect_status'   => 301,
            'redirect_external' => false,
            'redirect_to'       => 'http://example.com/bar',
        ];

        assertSame('/path/from', $route['path']);
        assertSame(2, $route['priority']);
        assertInstanceOf('Closure', $route['vars']);
        assertSame($expectedVars, $route['vars'](['foo' => 'bar']));
    }

    public function testRedirectToInternalEmptyIfCallbackReturnNoString()
    {
        $route = new RedirectRoute(
            'from',
            function (array $args) {
                return $args['bar'];
            }
        );

        $expectedVars = [
            'redirect_status'   => 301,
            'redirect_external' => false,
            'redirect_to'       => '',
        ];

        assertInstanceOf('Closure', $route['vars']);
        assertSame($expectedVars, $route['vars'](['bar' => 111]));
    }

    public function testRedirectToInternalEmptyString()
    {
        $route = new RedirectRoute('/', '');

        $expectedVars = [
            'redirect_status'   => 301,
            'redirect_external' => false,
            'redirect_to'       => '',
        ];

        assertSame($expectedVars, $route['vars']);
    }

    public function testAbsoluteRedirectToFromCallback()
    {
        $route = new RedirectRoute(
            '/path/from',
            function (array $args) {
                return 'http://example.com/'.$args['foo'];
            },
            ['priority' => 2]
        );

        $expectedVars = [
            'redirect_status'   => 301,
            'redirect_external' => false,
            'redirect_to'       => 'http://example.com/bar',
        ];

        assertSame('/path/from', $route['path']);
        assertSame(2, $route['priority']);
        assertInstanceOf('Closure', $route['vars']);
        assertSame($expectedVars, $route['vars'](['foo' => 'bar']));
    }

    public function testRedirectToExternalFromCallback()
    {
        $route = new RedirectRoute(
            '/path/from',
            function (array $args) {
                return 'https://'.$args['sub'].'.bar.it/';
            },
            ['redirect_external' => 1]
        );

        $expectedVars = [
            'redirect_status'   => 301,
            'redirect_external' => true,
            'redirect_to'       => 'https://www.bar.it/',
        ];

        assertSame('/path/from', $route['path']);
        assertInstanceOf('Closure', $route['vars']);
        assertSame($expectedVars, $route['vars'](['sub' => 'www']));
    }

    public function testRedirectToExternalEmptyIfCallbackReturnNoUrl()
    {
        $route = new RedirectRoute(
            '/path/from',
            function (array $args) {
                return $args['sub'].'.bar.it/';
            },
            ['redirect_external' => 1]
        );

        $expectedVars = [
            'redirect_status'   => 301,
            'redirect_external' => true,
            'redirect_to'       => '',
        ];

        assertSame('/path/from', $route['path']);
        assertInstanceOf('Closure', $route['vars']);
        assertSame($expectedVars, $route['vars'](['sub' => 'www']));
    }

    public function testStatusTo301IfBad()
    {
        $route = new RedirectRoute('/', 'http://example.com', ['redirect_status' => 111]);

        $expectedVars = [
            'redirect_status'   => 301,
            'redirect_external' => false,
            'redirect_to'       => 'http://example.com',
        ];

        assertSame($expectedVars, $route['vars']);
    }
}
