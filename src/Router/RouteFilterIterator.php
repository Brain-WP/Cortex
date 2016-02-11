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
use Brain\Cortex\Uri\WordPressUri;


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
     * RouteFilterIterator constructor.
     *
     * @param \Brain\Cortex\Route\RouteCollectionInterface $routes
     * @param \Brain\Cortex\Uri\WordPressUri               $uri
     */
    public function __construct(RouteCollectionInterface $routes, WordPressUri $uri)
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

        if (! empty($route['scheme']) && $route['scheme'] !== $this->uri->scheme()) {
            return false;
        }

        if (! empty($route['host']) && $route['host'] !== $this->uri->host()) {
            return false;
        }

        return true;
    }
}