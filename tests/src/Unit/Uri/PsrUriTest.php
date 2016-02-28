<?php
/*
 * This file is part of the cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Tests\Unit\Uri;

use Brain\Cortex\Tests\TestCase;
use Brain\Cortex\Uri\PsrUri;
use Brain\Monkey\Functions;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class PsrUriTest extends TestCase
{
    public function testGetSchemeSsl()
    {
        Functions::when('is_ssl')->justReturn(true);

        $uri = new PsrUri(['HTTP_HOST' => 'example.com']);

        assertSame('https', $uri->getScheme());
    }

    public function testGetSchemeNoSsl()
    {
        Functions::when('is_ssl')->justReturn(false);

        $uri = new PsrUri(['HTTP_HOST' => 'example.com']);

        assertSame('http', $uri->getScheme());
    }

    public function testGetHostFromHttpHost()
    {
        $uri = new PsrUri(['HTTP_HOST' => 'example.com']);

        assertSame('example.com', $uri->getHost());
    }

    public function testGetHostFromHttpServerName()
    {
        $uri = new PsrUri(['SERVER_NAME' => 'example.it']);

        assertSame('example.it', $uri->getHost());
    }

    public function testGetHostFromHomeUrl()
    {
        Functions::when('home_url')->justReturn('http://www.example.co.uk/wp/');

        $uri = new PsrUri(['meh' => 'meh']);

        assertSame('www.example.co.uk', $uri->getHost());
    }

    public function testGetHostStripPort()
    {
        $uri = new PsrUri(['HTTP_HOST' => 'example.com:8080']);

        assertSame('example.com', $uri->getHost());
    }

    public function testGetPath()
    {
        $uri = new PsrUri(['REQUEST_URI' => '/foo/bar/']);

        assertSame('foo/bar', $uri->getPath());
    }

    public function testGetPathStripHost()
    {
        $uri = new PsrUri(['REQUEST_URI' => 'http://example.com/foo/bar/']);

        assertSame('foo/bar', $uri->getPath());
    }

    public function testGetQuery()
    {
        $uri = new PsrUri(['QUERY_STRING' => 'foo=bar']);

        assertSame('foo=bar', $uri->getQuery());
    }

    public function testGetQueryStripQuestion()
    {
        $uri = new PsrUri(['QUERY_STRING' => '?foo=bar']);

        assertSame('foo=bar', $uri->getQuery());
    }

    public function testGetQueryEmpty()
    {
        $uri = new PsrUri(['HTTP_HOST' => 'example.com']);

        assertSame('', $uri->getQuery());
    }

    public function testToString()
    {
        Functions::when('is_ssl')->justReturn(true);

        $uri = new PsrUri([
            'HTTP_HOST'    => 'example.com:80',
            'REQUEST_URI'  => '/foo/bar/',
            'QUERY_STRING' => '?foo=bar',
        ]);

        assertSame('https:://example.com/foo/bar?foo=bar', (string) $uri);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testWithSchemeDisabled()
    {
        $uri = new PsrUri();
        $uri->withScheme('http');
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testWithUserInfoDisabled()
    {
        $uri = new PsrUri();
        $uri->withUserInfo('me', 'secret');
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testWithHostDisabled()
    {
        $uri = new PsrUri();
        $uri->withHost('example.it');
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testWithPortDisabled()
    {
        $uri = new PsrUri();
        $uri->withPort(8080);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testWithPathDisabled()
    {
        $uri = new PsrUri();
        $uri->withPath('/foo');
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testWithQueryDisabled()
    {
        $uri = new PsrUri();
        $uri->withQuery('foo=bar');
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testWithFragmentDisabled()
    {
        $uri = new PsrUri();
        $uri->withFragment('foo');
    }
}
