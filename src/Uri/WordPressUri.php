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


/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
class WordPressUri
{

    /**
     * @var bool
     */
    private $parsed = false;

    /**
     * @var array
     */
    private $server;

    /**
     * @var array
     */
    private $storage = [
        'scheme' => 'http',
        'host'   => '',
        'path'   => '/',
        'vars'   => [],
    ];

    /**
     * WordPressUri constructor.
     *
     * @param array $server
     */
    public function __construct(array $server = [])
    {
        $this->server = $server ? : $_SERVER;
    }

    /**
     * return string
     */
    public function scheme()
    {
        $this->parsed or $this->parse();

        return $this->storage['scheme'];
    }

    /**
     * return string
     */
    public function host()
    {
        $this->parsed or $this->parse();

        return $this->storage['host'];
    }

    /**
     * return string
     */
    public function path()
    {
        $this->parsed or $this->parse();

        return $this->storage['path'];
    }

    /**
     * return @array
     */
    public function vars()
    {
        $this->parsed or $this->parse();

        return $this->storage['vars'];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $this->parsed or $this->parse();

        $path = $this->path();
        $vars = $this->vars();
        $vars and $path .= '?'.http_build_query($vars);

        return sprintf('%s:://%s/%s', $this->scheme(), $this->host(), $path);
    }

    private function parse()
    {
        $scheme = is_ssl() ? 'https' : 'http';

        $host = $this->parseHost() ? : parse_url(home_url(), PHP_URL_HOST);
        $host = trim($host, '/');

        $pathArray = explode('?', $this->parsePath(), 2);
        $path = rawurldecode(trim($pathArray[0], '/'));
        $homePath = rawurldecode(trim(parse_url(home_url(), PHP_URL_PATH), '/'));
        (strpos($path, $homePath) === 0) and $path = trim(substr($path, strlen($homePath)), '/');
        empty($path) and $path = '/';

        $vars = [];
        if (isset($this->server['QUERY_STRING'])) {
            $queryString = ltrim($this->server['QUERY_STRING'], '?');
            parse_str($queryString, $vars);
        }

        $this->storage = compact('scheme', 'host', 'path', 'vars');
        $this->parsed = true;
    }

    /**
     * Contains code from Zend\Diactoros\ServerRequestFactory
     *
     * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
     * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD
     *            License
     *
     * @return string
     */
    private function parseHost()
    {
        $host = $this->server['HTTP_HOST'];
        if (empty($host)) {
            return isset($this->server['SERVER_NAME']) ? $this->server['SERVER_NAME'] : '';
        }

        if (is_string($host) && preg_match('|\:(\d+)$|', $host, $matches)) {
            $host = substr($host, 0, -1 * (strlen($matches[1]) + 1));
        }

        return $host;
    }

    /**
     * Contains code from Zend\Diactoros\ServerRequestFactory
     *
     * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
     * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD
     *            License
     *
     * @return string
     */
    private function parsePath()
    {
        $get = function ($key, array $values, $default = null) {
            if (array_key_exists($key, $values)) {
                return $values[$key];
            }

            return $default;
        };

        // IIS7 with URL Rewrite: make sure we get the unencoded url
        // (double slash problem).
        $iisUrlRewritten = $get('IIS_WasUrlRewritten', $this->server);
        $unencodedUrl = $get('UNENCODED_URL', $this->server, '');
        if ('1' == $iisUrlRewritten && ! empty($unencodedUrl)) {
            return $unencodedUrl;
        }

        $requestUri = $get('REQUEST_URI', $this->server);

        // Check this first so IIS will catch.
        $httpXRewriteUrl = $get('HTTP_X_REWRITE_URL', $this->server);
        if ($httpXRewriteUrl !== null) {
            $requestUri = $httpXRewriteUrl;
        }

        // Check for IIS 7.0 or later with ISAPI_Rewrite
        $httpXOriginalUrl = $get('HTTP_X_ORIGINAL_URL', $this->server);
        if ($httpXOriginalUrl !== null) {
            $requestUri = $httpXOriginalUrl;
        }

        if ($requestUri !== null) {
            return preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
        }

        $origPathInfo = $get('ORIG_PATH_INFO', $this->server);

        return empty($origPathInfo) ? '/' : $origPathInfo;
    }
}