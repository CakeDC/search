Changelog
=========

Release 2.5.1
-------------

https://github.com/CakeDC/search/tree/2.5.1

 * [8aba388](https://github.com/cakedc/search/commit/8aba388) Update Overview.md

Release 2.5.0
-------------

https://github.com/CakeDC/search/tree/2.5.0

 * [2638972](https://github.com/cakedc/search/commit/2638972) Refs #157 Changing the plugins defaults from named parameters to query strings
 * [fdac4c3](https://github.com/cakedc/search/commit/fdac4c3) CS Fix
 * [8cdb841](https://github.com/cakedc/search/commit/8cdb841) Update Installation.md

Release 2.4.1
-------------

https://github.com/CakeDC/search/tree/2.4.1

 * [23f24a6](https://github.com/cakedc/search/commit/23f24a6) Updating travis
 * [9fd58af](https://github.com/cakedc/search/commit/9fd58af) Updating .travis
 * [1d4d78c](https://github.com/cakedc/search/commit/1d4d78c) Updating PostFixture.php
 * [66d45c3](https://github.com/cakedc/search/commit/66d45c3) Updating the ArticleFixture.php

Release 2.4.0
-------------

https://github.com/CakeDC/search/tree/2.4.0

 * [ad94745](https://github.com/cakedc/search/commit/ad94745) improve merging of strict key based options
 * [251d233](https://github.com/cakedc/search/commit/251d233) Changing $this->_defaults['model'] to $this->_defaults['presetForm']['model']
 * [bd9a129](https://github.com/cakedc/search/commit/bd9a129) Add missing bracket
 * [e91e48a](https://github.com/cakedc/search/commit/e91e48a) Add support for ilike to SearchableBehavior
 * [b122dc7](https://github.com/cakedc/search/commit/b122dc7) Add case-insensitive support for Postgres
 * [6689123](https://github.com/cakedc/search/commit/6689123) Making the 2nd arg of the PrgComponent constructor optional as it should be
 * [27abbf1](https://github.com/cakedc/search/commit/27abbf1) Refs https://github.com/CakeDC/search/pull/144 fixing the undefined var $settings, changing it to $this->_defaults
 * [a3c4c3c](https://github.com/cakedc/search/commit/a3c4c3c) Avoid Undefined index error due to empty criteria of "type = lookup"

Release 2.3.0
-------------

https://github.com/CakeDC/search/tree/2.3.0

* [4b7cfdb](https://github.com/CakeDC/search/commit/4b7cfdb) Made the prg component initialization configurable, see https://github.com/CakeDC/search/issues/138
* [2fd5d97](https://github.com/CakeDC/search/commit/2fd5d97) Changing Set to Hash in an App::uses() call
* [eabe675](https://github.com/CakeDC/search/commit/eabe675) Updating the deprecated Set to Hash
* [afc8f5d](https://github.com/CakeDC/search/commit/afc8f5d) Fixes https://github.com/CakeDC/search/pull/133
* [f72a97b](https://github.com/CakeDC/search/commit/f72a97b) Field value is actually null
* [c4df770](https://github.com/CakeDC/search/commit/c4df770) Fix emptyValue for query strings, before it was only possible for named params.
* [aa87da7](https://github.com/CakeDC/search/commit/aa87da7) Renaming the test file that triggers all tests
* [6ae2212](https://github.com/CakeDC/search/commit/6ae2212) Fixing the filterArgs initialization
* [57a4ddc](https://github.com/CakeDC/search/commit/57a4ddc) Fixes a missing word, see https://github.com/CakeDC/search/pull/115
* [24f8520](https://github.com/CakeDC/search/commit/24f8520) Fixed one test and removed another that isn't necessary anymore since always allowing wildcards
* [6fbf596](https://github.com/CakeDC/search/commit/6fbf596) Partly reconstructed testSubQueryEmptyCondition()
* [eadc9a8](https://github.com/CakeDC/search/commit/eadc9a8) Replaced skipIf() with markTestSkipped()
* [f26cbb0](https://github.com/CakeDC/search/commit/f26cbb0) General DocBlock, comment and code cleanup in PrgComponentTest
* [a45388e](https://github.com/CakeDC/search/commit/a45388e) Removed unused variable in SearchableBehavior
* [872499d](https://github.com/CakeDC/search/commit/872499d) General DocBlock, comment and code cleanup of test suite
* [30d69cb](https://github.com/CakeDC/search/commit/30d69cb) General DocBlock, comment and code cleanup of fixtures
* [07d812f](https://github.com/CakeDC/search/commit/07d812f) Fixed @license part order in all files
* [90c9916](https://github.com/CakeDC/search/commit/90c9916) General DocBlock, comment and code cleanup of SearchableBehaviorTest
* [363cdc7](https://github.com/CakeDC/search/commit/363cdc7) Removed Behaviors->detach, replaced Behaviors->attach by Behaviors->load()
* [e3afc10](https://github.com/CakeDC/search/commit/e3afc10) Always allow custom wildcards