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
use Brain\Cortex\Uri\WordPressUri;
use Brain\Monkey\Functions;
use Psr\Http\Message\UriInterface as PrsUriInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class WordPressUriTest extends TestCase
{
    private function psrUriFromUrl($url)
    {
        $parts = parse_url($url);
        $query = empty($parts['query']) ? '' : $parts['query'];
        $uri = \Mockery::mock(PrsUriInterface::class);
        $uri->shouldReceive('getScheme')->andReturn($parts['scheme']);
        $uri->shouldReceive('getHost')->andReturn($parts['host']);
        $uri->shouldReceive('getPath')->andReturn($parts['path']);
        $uri->shouldReceive('getQuery')->andReturn($query);

        return $uri;
    }

    public function testNoHomePath()
    {
        Functions::when('home_url')->justReturn('https://www.example.com/');

        $uri = $this->psrUriFromUrl('https://www.example.com/foo/bar.php?meh=1');
        $wpUri = new WordPressUri($uri);

        assertSame('https', $wpUri->scheme());
        assertSame('www.example.com', $wpUri->host());
        assertSame('foo/bar.php', $wpUri->path());
        assertSame(['foo', 'bar.php'], $wpUri->chunks());
        assertSame(['meh' => '1'], $wpUri->vars());
    }

    public function testNoQueryVars()
    {
        Functions::when('home_url')->justReturn('https://www.example.com');

        $uri = $this->psrUriFromUrl('https://www.example.com/foo/bar/');
        $wpUri = new WordPressUri($uri);

        assertSame('https', $wpUri->scheme());
        assertSame('www.example.com', $wpUri->host());
        assertSame('foo/bar', $wpUri->path());
        assertSame(['foo', 'bar'], $wpUri->chunks());
        assertSame([], $wpUri->vars());
    }

    public function testHomeUrlHomePath()
    {
        Functions::when('home_url')->justReturn('https://www.example.com/foo/');

        $uri = $this->psrUriFromUrl('https://www.example.com/foo/');
        $wpUri = new WordPressUri($uri);

        assertSame('https', $wpUri->scheme());
        assertSame('www.example.com', $wpUri->host());
        assertSame('/', $wpUri->path());
        assertSame([], $wpUri->chunks());
        assertSame([], $wpUri->vars());
    }

    public function testHomeUrlNoHomePath()
    {
        Functions::when('home_url')->justReturn('https://www.example.com/');

        $uri = $this->psrUriFromUrl('https://www.example.com/');
        $wpUri = new WordPressUri($uri);

        assertSame('https', $wpUri->scheme());
        assertSame('www.example.com', $wpUri->host());
        assertSame('/', $wpUri->path());
        assertSame([], $wpUri->chunks());
        assertSame([], $wpUri->vars());
    }
}
