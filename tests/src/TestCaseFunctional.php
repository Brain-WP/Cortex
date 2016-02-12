<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Tests;

use Andrew\StaticProxy;
use Brain\Cortex;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
class TestCaseFunctional extends TestCase
{
    protected static function buildPsrRequest($url, $method = 'GET')
    {
        $parts = parse_url($url);
        $query = empty($parts['query']) ? '' : $parts['query'];
        $uri = \Mockery::mock(UriInterface::class);
        $uri->shouldReceive('getScheme')->andReturn($parts['scheme']);
        $uri->shouldReceive('getHost')->andReturn($parts['host']);
        $uri->shouldReceive('getPath')->andReturn($parts['path']);
        $uri->shouldReceive('getQuery')->andReturn($query);

        $request = \Mockery::mock(RequestInterface::class);
        $request->shouldReceive('getUri')->andReturn($uri);
        $request->shouldReceive('getMethod')->andReturn($method);

        return $request;
    }

    protected function setUp()
    {
        parent::setUp();
        defined('WP_DEBUG') or define('WP_DEBUG', true);
    }

    protected function tearDown()
    {
        $proxy = new StaticProxy(Cortex::class);
        /** @noinspection PhpUndefinedFieldInspection */
        $proxy->booted = false;
        /** @noinspection PhpUndefinedFieldInspection */
        $proxy->late = false;

        parent::tearDown();
    }
}
