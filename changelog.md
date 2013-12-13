# Changes of the Search plugin

List of changes done to the plugin versions.

## 2.3

* ```defaultValue``` is now available in case no value has been passed and we need to trigger the filters.
* Confusing and redundant types have been removed. Either use type ```value``` (exact match), ```like``` (partial match) or expression/subquery/query.
* Query strings now work properly. ```$this->passedArgs``` has been deprecated. Please use ```$this->Prg->parsedParams()``` instead from now on.

## 2.2

* ```emptyValue``` is now available for fields to make it work with "default NULL" fields and ```allowEmpty``` set to true. See example below.

## Changelog before 2.2

Not available.
