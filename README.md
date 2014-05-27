## Cortex ##

Cortex is **routing system for WordPress** based on the well-known and affordable [**Symfony Routing Component**][1].

![Cortex][2]

Using Cortex is possible tell WordPress what to do when a specific url, or better, a specific class of urls, are visited by users.

It is a package (not full plugin) and makes use of [**Composer**][3] to be embedded in larger projects.

It is part of a [**The Brain WP Project**][4].

API
---

Cortex package comes with an API that ease its usage, without having to get, instantiate or digging into package objects. API is defined in a class, stored in the Brain (Pimple) container with the id: `"cortex.api"`. So is possible to get it using Brain instance, something like: `$api = Brain\Container::instance()->get("cortex.api")`, and then call all API function on the instance got in that way. However that's not very easy to use, this is the reason why package also comes with a **facade class**. The term is not referred to [façade pattern][5], but more to [Laravel facades][6], whence the approach (not actual code) comes from: no real static method is present in the class, but a single `__callstatic` method that *proxy* API methods to proper instantiated objects.

The facade class is named **Routes** inside Brain namespace. Using it, add an (very simple) route is something like:

    Brain\Routes::add( '/home' )->defaults( [ 'pagename' => 'home' ] )->template( 'custom-home.php' );


Embed in OOP projects
---------------------

The static facade class is easy to use, however using in that way inside other classes, create there hardcoded dependency to Cortex. Moreover, unit testing other classes in isolation becomes pratically impossible. To solve these problems, the easiest way is to use composition via dependency injection. In facts, the Brain\Routes facade class can be used in dynamic way, e.g. the simple route added above can also be added like so:

    $routes = new Brain\Routes;
    $routes->add( '/home' )->defaults( [ 'pagename' => 'home' ] )->template( 'custom-home.php' );

Looking at Brain\Routes class code, you'll see there is **absolutely no difference** in the two methods, but using the latter is possible to inject an instance of the class inside other classes.

Requirements
------------

 - PHP 5.4+
 - Composer (to install)
 - WordPress 3.9 (it maybe works with earlier versions, but it's not tested and versions < 3.9 will never supported).

Installation
------------

You need Composer to install the package. It is hosted on Packagist, so the only thing needed is insert `"brain/cortex": "dev-master"` in your `composer.json` `require` object

    {
        "require": {
            "php": ">=5.4",
            "brain/cortex": "dev-master"
        }
    }

See [Composer documentation][7] on how to install Composer itself, and packages. 

Developers & Contributors
-------------------------

Package is open to contributors and pull requests. It comes with a set of unit tests written for PHPUnit suite. Please be sure all tests pass before submit a PR. To run tests, please install package in stand-alone mode (i.e 'vendor' folder is inside package folder).

License
-------
Cortex own code is licensed under GPLv2+. Through Composer, it install code from:

 - [Composer][8] (MIT)
 - [Brain][9] (GPLv2+)
 - [Amygdala][10] (GPLv2+)
 - [Striatum][11] (GPLv2+)
 - [Pimple][12] (MIT) - required by Brain -
 - [PHPUnit][13] (BSD-3-Clause) - only dev install -
 - [Mockery][14] (BSD-3-Clause) - only dev install -
 - [WP_Mock][15] (GPL-v2+) - only dev install -


  [1]: http://symfony.com/doc/current/components/routing/introduction.html
  [2]: https://googledrive.com/host/0Bxo4bHbWEkMscmJNYkx6YXctaWM/cortex.png
  [3]: https://getcomposer.org/
  [4]: http://giuseppe-mazzapica.github.io/Brain/
  [5]: http://en.wikipedia.org/wiki/Facade_pattern
  [6]: http://laravel.com/docs/facades
  [7]: https://getcomposer.org/doc/
  [8]: https://getcomposer.org/
  [9]: https://github.com/Giuseppe-Mazzapica/Brain
  [10]: https://github.com/Giuseppe-Mazzapica/Amygdala
  [11]: https://github.com/Giuseppe-Mazzapica/Striatum
  [12]: http://pimple.sensiolabs.org/
  [13]: http://phpunit.de/
  [14]: https://github.com/padraic/mockery
  [15]: https://github.com/10up/wp_mock