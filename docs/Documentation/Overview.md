Overview
========

PRG Component Features
----------------------

The [Prg Component](../../src/Controller/Component/PrgComponent.php) implements the PRG pattern so you can use it separately from search tasks when you need it.

The component maintains passed and named parameters or query string variables that come as POST parameters and transform it to the named during redirect, and sets Controller::data back if the GET method was used during component call.

Most importantly the component acts as the glue between your app and the searchable behavior.

You can attach the component to your controller. Here is an example using defaults already set in the component itself.

```php
public $components = array('Search.Prg' => array(
	// options for preset form method
	'presetForm' => array(
		// 'table' can be 'null' or a default table name
		'table' => null,
		// 'formName' can be 'null' or a default form name
		'formName' => null
	),
	// options for commonProcess method
	'commonProcess' => array(
		'formName' => null,
		'keepPassed' => true,
		'action' => null,
		'tableMethod' => 'validateSearch',
		'allowedParams' => array(),
		'filterEmpty' => false
	)
));
```

PrgComponent::presetForm Options
--------------------------------

* **table:** Table name or null, by default ```null```.
* **formName:** Form name or null, by default ```null```.

PrgComponent::commonProcess Options
-----------------------------------

The ```commonProcess()``` method defined in the Prg component allows you to inject search in any controller with just 1-2 lines of additional code. You should pass the model name that is used for search. By default it is ```Controller::$modelClass```.

Additional options parameters.

* **formName:** Search form name.
* **keepPassed:** Parameter that describes if you need to merge ```passedArgs``` to the url where you will be redirected to after post.
* **action:** Sometimes you want to have different actions for post and get. In this case you can define get action using this parameter.
* **tableMethod:** Method used to filter named parameters, passed from the form. By default it is validateSearch, and it defined in Searchable behavior.
