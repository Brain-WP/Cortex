<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Uri;

use Psr\Http\Message\UriInterface as PsrUriInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
final class WordPressUri implements UriInterface
{
    /**
     * @var \Psr\Http\Message\UriInterface
     */
    private $uri;

    /**
     * WordPressUri constructor.
     *
     * @param \Psr\Http\Message\UriInterface $uri
     */
    public function __construct(PsrUriInterface $uri)
    {
        $this->uri = $uri;
    }

    /**
     * @inheritdoc
     */
    public function scheme()
    {
        return $this->uri->getScheme();
    }

    /**
     * @inheritdoc
     */
    public function host()
    {
        return $this->uri->getHost();
    }

    /**
     * @inheritdoc
     */
    public function chunks()
    {
        $path = $this->path();

        return $path === '/' ? [] : explode('/', $path);
    }

    /**
     * @inheritdoc
     */
    public function path()
    {
        /*
         * If WordPress is installed in a subfolder and home url is something like
         * `example.com/subfolder` we need to strip down `/subfolder` from path and build a path
         * for route matching that is relative to home url.
         */
        $homePath = trim(parse_url(home_url(), PHP_URL_PATH), '/');
        $path = trim($this->uri->getPath(), '/');
        if ($homePath && strpos($path, $homePath) === 0) {
            $path = trim(substr($path, strlen($homePath)), '/');
        }

        return $path ? : '/';
    }

    /**
     * @inheritdoc
     */
    public function vars()
    {
        $vars = [];
        parse_str($this->uri->getQuery(), $vars);

        return $vars;
    }
}
