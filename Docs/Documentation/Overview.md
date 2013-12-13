Overview
========

PRG Component features
----------------------

The Prg component implements the PRG pattern so you can use it separately from search tasks when you need it.

The component maintains passed and named parameters or query string variables that come as POST parameters and transform it to the named during redirect, and sets Controller::data back if the GET method was used during component call.

Most importantly the component acts as the glue between your app and the searchable behavior.

You can attach the component to your controller. Here is an example using defaults already set in the component itself.

```php
public $components = array('Search.Prg' => array(
	//Options for preset form method
	'presetForm' => array(
		'paramType' => 'named' // or 'querystring'
		'model' => null // or a default model name
	),
	//Options for commonProcess method
	'commonProcess' => array(
		'formName' => null,
		'keepPassed' => true,
		'action' => null,
		'modelMethod' => 'validateSearch',
		'allowedParams' => array(),
		// ParamType can be 'named' or 'querystring'
		'paramType' => 'named',
		'filterEmpty' => false
	)
));
```

Prg::commonProcess Method Usage
-------------------------------

The ```commonProcess()``` method defined in the Prg component allows you to inject search in any controller with just 1-2 lines of additional code. You should pass model name that used for search. By default it is Controller::modelClass model.

Additional options parameters.

* form        - search form name.
* keepPassed  - parameter that describe if you need to merge passedArgs to Get url where you will Redirect after Post
* action      - sometimes you want to have different actions for post and get. In this case you can define get action using this parameter.
* modelMethod - method, used to filter named parameters, passed from form. By default it is validateSearch, and it defined in Searchable behavior.