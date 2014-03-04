CakeDC Search Plugin
========================

This **Search** plugin enables developers to quickly implement [the POST-Redirect-GET pattern](Docs/Documentation/Post-Redirect-Get.md).

The Search plugin is an easy way to implement PRG into your application, and provides you with a paginate-able search in any controller. It supports simple methods to search inside models using strict and non-strict comparing, but also allows you to implement any complex type of searching.

* **PRG Component:** The component will turn GET parameters into POST to populate a form and vice versa.
* **Search Behaviour:** The behaviour will generate search conditions pased on the provided GET parameters.

[![Bake Status](https://secure.travis-ci.org/CakeDC/search.png?branch=master)](http://travis-ci.org/CakeDC/search)
[![Test Coverage](https://coveralls.io/repos/CakeDC/search/badge.png?branch=master)](https://coveralls.io/r/CakeDC/search?branch=master)
[![Downloads](https://poser.pugx.org/CakeDC/search/d/total.png)](https://packagist.org/packages/CakeDC/search)
[![Latest Version](https://poser.pugx.org/CakeDC/search/v/stable.png)](https://packagist.org/packages/CakeDC/search)

This is *not* a Search Engine or Index
--------------------------------------

As already mentioned before this plugin helps you to implement searching for data using [the PRG pattern](Docs/Documentation/Post-Redirect-Get.md). It is **not** any kind of search engine implementation or search index builder but it can be used to search a search index like Elastic Search or Sphinx as well.

Requirements
------------

* CakePHP 2.4+
* PHP 5.2.8+

Documentation
-------------

For documentation, as well as tutorials, see the [Docs](Docs/Home.md) directory of this repository.

Support
-------

For bugs and feature requests, please use the [issues](https://github.com/CakeDC/search/issues) section of this repository. 

Commercial support is also available, [contact us](http://cakedc.com/contact) for more information.

Contributing
------------

If you'd like to contribute new features, enhancements or bug fixes to the plugin, just read our [Contribution Guidelines](http://cakedc.com/plugins) for detailed instructions.

License
-------

Copyright 2007-2014 Cake Development Corporation (CakeDC). All rights reserved.

Licensed under the [MIT](http://www.opensource.org/licenses/mit-license.php) License. Redistributions of the source code included in this repository must retain the copyright notice found in each file.