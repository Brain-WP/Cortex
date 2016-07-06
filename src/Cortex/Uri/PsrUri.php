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
final class PsrUri implements PsrUriInterface
{
    /**
     * @var array
     */
    private $storage = [
        'scheme'       => 'http',
        'host'         => '',
        'path'         => '/',
        'query_string' => '',
    ];

    /**
     * @var array
     */
    private $server;

    /**
     * @var bool
     */
    private $parsed = false;

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
     * @inheritdoc
     */
    public function getScheme()
    {
        $this->parsed or $this->marshallFromServer();

        return $this->storage['scheme'];
    }

    /**
     * @inheritdoc
     */
    public function getHost()
    {
        $this->parsed or $this->marshallFromServer();

        return $this->storage['host'];
    }

    /**
     * Not really implemented, because Cortex does not need it.
     *
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function getPort()
    {
        return;
    }

    /**
     * @inheritdoc
     */
    public function getPath()
    {
        $this->parsed or $this->marshallFromServer();

        return $this->storage['path'];
    }

    /**
     * @inheritdoc
     */
    public function getQuery()
    {
        $this->parsed or $this->marshallFromServer();

        return $this->storage['query_string'];
    }

    /**
     * Not really implemented, because Cortex does not need it.
     *
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function getAuthority()
    {
        return '';
    }

    /**
     * Not really implemented, because Cortex does not need it.
     *
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function getUserInfo()
    {
        return '';
    }

    /**
     * Not really implemented, because Cortex does not need it.
     *
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function getFragment()
    {
        return '';
    }

    /**
     * Disabled.
     *
     * @inheritdoc
     */
    public function withScheme($scheme)
    {
        throw new \BadMethodCallException(sprintf('%s is read-only.', __CLASS__));
    }

    /**
     * Disabled.
     *
     * @inheritdoc
     */
    public function withUserInfo($user, $password = null)
    {
        throw new \BadMethodCallException(sprintf('%s is read-only.', __CLASS__));
    }

    /**
     * Disabled.
     *
     * @inheritdoc
     */
    public function withHost($host)
    {
        throw new \BadMethodCallException(sprintf('%s is read-only.', __CLASS__));
    }

    /**
     * Disabled.
     *
     * @inheritdoc
     */
    public function withPort($port)
    {
        throw new \BadMethodCallException(sprintf('%s is read-only.', __CLASS__));
    }

    /**
     * Disabled.
     *
     * @inheritdoc
     */
    public function withPath($path)
    {
        throw new \BadMethodCallException(sprintf('%s is read-only.', __CLASS__));
    }

    /**
     * Disabled.
     *
     * @inheritdoc
     */
    public function withQuery($query)
    {
        throw new \BadMethodCallException(sprintf('%s is read-only.', __CLASS__));
    }

    /**
     * Disabled.
     *
     * @inheritdoc
     */
    public function withFragment($fragment)
    {
        throw new \BadMethodCallException(sprintf('%s is read-only.', __CLASS__));
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        $this->parsed or $this->marshallFromServer();

        $url = sprintf('%s:://%s/%s', $this->getScheme(), $this->getHost(), $this->getPath());
        $query = $this->getQuery();

        return $query ? "{$url}?{$query}" : $url;
    }

    /**
     * Parse server array to find url components.
     */
    private function marshallFromServer()
    {
        $scheme = is_ssl() ? 'https' : 'http';

        $host = $this->marshallHostFromServer() ? : parse_url(home_url(), PHP_URL_HOST);
        $host = trim($host, '/');

        $pathArray = explode('?', $this->marshallPathFromServer(), 2);
        $path = trim($pathArray[0], '/');

        empty($path) and $path = '/';

        $query_string = '';
        if (isset($this->server['QUERY_STRING'])) {
            $query_string = ltrim($this->server['QUERY_STRING'], '?');
        }

        $this->storage = compact('scheme', 'host', 'path', 'query_string');
        $this->parsed = true;
    }

    /**
     * Parse server array to find url host.
     *
     * Contains code from Zend\Diactoros\ServerRequestFactory
     *
     * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
     * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD
     *            License
     *
     * @return string
     */
    private function marshallHostFromServer()
    {
        $host = isset($this->server['HTTP_HOST']) ? $this->server['HTTP_HOST'] : '';
        if (empty($host)) {
            return isset($this->server['SERVER_NAME']) ? $this->server['SERVER_NAME'] : '';
        }

        if (is_string($host) && preg_match('|\:(\d+)$|', $host, $matches)) {
            $host = substr($host, 0, -1 * (strlen($matches[1]) + 1));
        }

        return $host;
    }

    /**
     * Parse server array to find url path.
     *
     * Contains code from Zend\Diactoros\ServerRequestFactory
     *
     * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
     * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD
     *            License
     *
     * @return string
     */
    private function marshallPathFromServer()
    {
        $get = function ($key, array $values, $default = null) {
            return array_key_exists($key, $values) ? $values[$key] : $default;
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
