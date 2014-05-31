## Cortex ##

Cortex is **routing system for WordPress** based on the well-known and affordable [**Symfony Routing Component**][1].

![Cortex][2]

Using Cortex is possible tell WordPress what to do when a specific url, or better, a specific class of urls, are visited by users.

It is a package (not full plugin) and makes use of [**Composer**][3] to be embedded in larger projects.

It is part of a [**The Brain WP Project**][4].

Table of Contents
-----------------

[TOC]

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