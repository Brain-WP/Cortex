<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Router;

use Brain\Cortex\Route\RouteCollectionInterface;
use Brain\Cortex\Route\RouteInterface;
use Brain\Cortex\Uri\UriInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
final class RouteFilterIterator extends \FilterIterator
{
    /**
     * @var \Brain\Cortex\Uri\WordPressUri
     */
    private $uri;

    /**
     * @param  string $routeHost
     * @param  string $serverHost
     * @return bool
     */
    private static function checkHost($routeHost, $serverHost)
    {
        if (strpos($routeHost, '*') === false) {
            return $routeHost === $serverHost;
        }

        if (strpos($routeHost, '*.') === 0) {
            return fnmatch($routeHost, $serverHost) || fnmatch(substr($routeHost, 2), $serverHost);
        }

        return fnmatch($routeHost, $serverHost);
    }

    /**
     * RouteFilterIterator constructor.
     *
     * @param \Brain\Cortex\Route\RouteCollectionInterface $routes
     * @param \Brain\Cortex\Uri\UriInterface               $uri
     */
    public function __construct(RouteCollectionInterface $routes, UriInterface $uri)
    {
        parent::__construct($routes);
        $this->uri = $uri;
    }

    /**
     * @inheritdoc
     */
    public function accept()
    {
        /** @var RouteInterface $route */
        $route = $this->getInnerIterator()->current();
        if (! $route instanceof RouteInterface) {
            return false;
        }

        $scheme = strtolower((string)$route['scheme']);
        if (! empty($scheme) && $scheme !== $this->uri->scheme()) {
            return false;
        }

        $host = filter_var(strtolower((string)$route['host']), FILTER_SANITIZE_URL);
        if (! empty($host) && ! self::checkHost($route['host'], $this->uri->host())) {
            return false;
        }

        return true;
    }
}
