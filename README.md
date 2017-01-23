Cortex
======

[![Travis CI](https://img.shields.io/travis/Brain-WP/Cortex.svg?branch=refactoring-fastroute&style=flat-square)](https://travis-ci.org/Brain-WP/Cortex)
[![codecov.io](https://img.shields.io/codecov/c/github/Brain-WP/Cortex.svg?style=flat-square&branch=refactoring-fastroute)](https://codecov.io/github/Brain-WP/Cortex?branch=refactoring-fastroute)
[![MIT license](https://img.shields.io/packagist/l/brain/cortex.svg?style=flat-square)](http://opensource.org/licenses/MIT)

------

**Cortex is routing system for WordPress** based on [FastRoute](https://github.com/nikic/FastRoute)

## Start using Cortex

First of all ensure Composer autoload is loaded.

After that "boot" Cortex:

```php
Brain\Cortex::boot();
```

This can be done as soon as you can, no need to wrap in a hook.

It will not work after [`'do_parse_request'`](https://developer.wordpress.org/reference/hooks/do_parse_request/)
as been fired.

## Adding routes

To add routes, it is possible to use `'cortex.routes'` hook, that passes an instance of
`RouteCollectionInterface`:

```php
use Brain\Cortex\Route\RouteCollectionInterface;
use Brain\Cortex\Route\QueryRoute;

add_action('cortex.routes', function(RouteCollectionInterface $routes) {
	
	$routes->addRoute(new QueryRoute(
		'{type:[a-z]+}/latest',
		function(array $matches) {
		  return [
		    'post_type'      => $matches['type'],
		    'posts_per_page' => 5,
		    'orderby'        => 'date',
		    'order'          => 'ASC'
		  ];
		}
	));
});
```

The route pattern (1st argument) syntax is inherited from FastRoute.

The callback passed as second argument receives the array of matches (`$routeInfo[2]` in FastRoute)
and has to return an array of arguments for `WP_Query`.


##`QueryRoute` arguments

`QueryRoute` constructor accepts as 3rd argument an array of options for
route configuration.

One of them is **"template"** to force WordPress use a template when the route matches:

```php
add_action('cortex.routes', function(RouteCollectionInterface $routes) {
	
	$routes->addRoute(new QueryRoute(
		'post/latest',
		function(array $matches) {
		  return [
		    'orderby'        => 'date',
		    'order'          => 'DESC'
		  ];
		},
		['template' => 'latest.php']
	));
});
```

As shown above,`template` argument can be a relative path to theme (or child theme) folder.

To use a template that resides outside theme folder, `template` argument need to be full absolute path
to the template file to use.


There are other arguments, among them:

 - "before" and "after", that are callbacks run respectively before and after the
   callback that returns query arguments is called
 - "host" to make the route match only for specific host
 - "method" to make the route match only for specific HTTP method (e.g. `POST` or `GET`)
 - "scheme" to make the route match only for specific HTTP scheme (e.g. `https` or `http`)
 - "group" to use configuration from one or more "route groups"
 - "priority" to force the route evaluation in specific order (lower priority first)
 - "merge_query_string" to allow (default) or avoid url query string are merged as
   query argument to anything returned by route callback
   
## Route groups

A route group is a way to share common settings among routes.

Before assign groups to routes, we need to add groups.

That can be done using `'cortex.groups'` hook, that pass an instance of `GroupCollectionInterface`:

```php
use Brain\Cortex\Route\RouteCollectionInterface;
use Brain\Cortex\Group\GroupCollectionInterface;
use Brain\Cortex\Route\QueryRoute;
use Brain\Cortex\Group\Group;

add_action('cortex.groups', function(GroupCollectionInterface $groups) {
	
	$groups->addGroup(new Group([
	    'id'       => 'archive-group',
	    'template' => 'archive.php',
	    'before'   => function() {
	       // do something before route callback
	    }
	]));
});

add_action('cortex.routes', function(RouteCollectionInterface $routes) {
	
	$routes->addRoute(new QueryRoute(
	    '^post/latest$',
	    function(array $matches) {
	        return [
	            'orderby'        => 'date',
	            'order'          => 'DESC'
	        ];
	    },
	    ['group' => 'archive-group']
	));
	
	$routes->addRoute(new QueryRoute(
	    'post/oldest',
	    function(array $matches) {
	        return [
	            'orderby'        => 'date',
	            'order'          => 'ASC'
	         ];
	     },
	     ['group' => 'archive-group']
	));
});
```

A group is instantiated passing an array of values to its constructor.
The value "id" is required. All other values are optional, and can be used to set
any route property (array items in 3rd param of `QueryRoute` constructor).

To use properties from a group in a route, the group id has to be set in the `'group'` 
route property.

`'group'` property also accepts an array of group ids, to assign properties
from multiple groups.


## Redirect routes

`QueryRoute` is just one of the routes shipped with Cortex.
There are others and it is possible to write custom routes implementing `Brain\Cortex\Route\RouteInterface`.

Another implementation included in Cortex is `RedirectRoute`. As the name suggests,
it is used to redirect urls to other urls.

```php
use Brain\Cortex\Route\RouteCollectionInterface;
use Brain\Cortex\Route\RedirectRoute;

add_action('cortex.routes', function(RouteCollectionInterface $routes) {
	
	$routes->addRoute(new RedirectRoute(
		'old/url/{postname}',
		function(array $matches) {
		  return 'new/url/' . $matches['postname'];
		}
	));
});
```

`RedirectRoute` accepts an array of options as well.

Using option is possible to configure HTTP status code to use (`'redirect_status'` option, default 302)
and if allows or not redirect to external urls (`'redirect_external'` option, default false).


----------


## Installation

Via Composer, require `brain/cortex` in version `~1.0.0`.

`composer require brain/cortex:~1.0.0`

You may need to lessen your project's minimum stability requirements.

`composer config minimum-stability dev`

## Minimum Requirements

- PHP 5.5+
- Composer to install

## Dependencies

- Any version of PSR7 interfaces (no implementation required)
- FastRoute

## License

MIT
