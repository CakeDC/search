# Search Plugin for CakePHP #

Version 2.2 for cake 2.x

The Search plugin allows you to make any kind of data searchable, enabling you to implement a robust searching rapidly.

The Search plugin is an easy way to include search into your application, and provides you with a paginate-able search in any controller.

It supports simple methods to search inside models using strict and non-strict comparing, but also allows you to implement any complex type of searching.

## UPDATE for 2.2 - 2013-01-16 Mark Scherer

* `emptyValue` is now available for fields to make it work with "default NULL" fields and `allowEmpty` set to true. See example below.

## Sample of usage ##

An example of how to implement complex searching in your application.

Model code:

	class Article extends AppModel {

		public $actsAs = array('Search.Searchable');
		public $belongsTo = array('User');
		public $hasAndBelongsToMany = array('Tag' => array('with' => 'Tagged'));

		public $filterArgs = array(
			'title' => array('type' => 'like'),
			'status' => array('type' => 'value'),
			'blog_id' => array('type' => 'value'),
			'search' => array('type' => 'like', 'field' => 'Article.description'),
			'range' => array('type' => 'expression', 'method' => 'makeRangeCondition', 'field' => 'Article.views BETWEEN ? AND ?'),
			'username' => array('type' => 'like', 'field' => array('User.username', 'UserInfo.first_name')),
			'tags' => array('type' => 'subquery', 'method' => 'findByTags', 'field' => 'Article.id'),
			'filter' => array('type' => 'query', 'method' => 'orConditions'),
			'enhanced_search' => array('type' => 'like', 'encode' => true, 'before' => false, 'after' => false, 'field' => array('ThisModel.name', 'OtherModel.name')),
		);

		public function findByTags($data = array()) {
			$this->Tagged->Behaviors->attach('Containable', array('autoFields' => false));
			$this->Tagged->Behaviors->attach('Search.Searchable');
			$query = $this->Tagged->getQuery('all', array(
				'conditions' => array('Tag.name'  => $data['tags']),
				'fields' => array('foreign_key'),
				'contain' => array('Tag')
			));
			return $query;
		}

		public function orConditions($data = array()) {
			$filter = $data['filter'];
			$cond = array(
				'OR' => array(
					$this->alias . '.title LIKE' => '%' . $filter . '%',
					$this->alias . '.body LIKE' => '%' . $filter . '%',
				));
			return $cond;
		}
	}

Associated snippet for the controller class:

	class ArticlesController extends AppController {
		public $components = array('Search.Prg');

		public $presetVars = true; // using the model configuration

		public function find() {
			$this->Prg->commonProcess();
			$this->paginate['conditions'] = $this->Article->parseCriteria($this->passedArgs);
			$this->set('articles', $this->paginate());
		}
	}

or verbose (and overriding the model configuration):

	class ArticlesController extends AppController {
		public $components = array('Search.Prg');

		public $presetVars = array(
			'title' => array('type' => 'value'),
			'status' => array('type' => 'checkbox'),
			'blog_id' => array('type' => 'lookup', 'formField' => 'blog_input', 'modelField' => 'title', 'model' => 'Blog')
		);

		public function find() {
			$this->Prg->commonProcess();
			$this->Paginator->settings['conditions'] = $this->Article->parseCriteria($this->passedArgs);
			$this->set('articles', $this->Paginator->paginate());
		}
	}

The `find.ctp` view is the same as `index.ctp` with the addition of the search form:

	echo $this->Form->create('Article', array(
		'url' => array_merge(array('action' => 'find'), $this->params['pass'])
	));
	echo $this->Form->input('title', array('div' => false));
	echo $this->Form->input('blog_id', array('div' => false, 'options' => $blogs));
	echo $this->Form->input('status', array('div' => false, 'multiple' => 'checkbox', 'options' => array('open', 'closed')));
	echo $this->Form->input('username', array('div' => false));
	echo $this->Form->submit(__('Search'), array('div' => false));
	echo $this->Form->end();

In this example on model level shon example of search by OR condition. For this purpose defined method orConditions and added filter arg `array('name' => 'filter', 'type' => 'query', 'method' => 'orConditions')`.

## Advanced usage ##

		public $filterArgs = array(
			// match results with `%searchstring`:
			'search_exact_beginning' => array('type' => 'like', 'encode' => true, 'before' => true, 'after' => false),
			// match results with `searchstring%`:
			'search_exact_end' => array('type' => 'like', 'encode' => true, 'before' => false, 'after' => true),
			// match results with `__searchstring%`:
			'search_special_like' => array('type' => 'like', 'encode' => true, 'before' => '__', 'after' => '%'),
			// use custom wildcards in the frontend (instead of * and ?):
			'search_custom_like' => array('type' => 'like', 'encode' => true, 'before' => false, 'after' => false, 'wildcardAny' => '%', 'wildcardOne' => '_'),
			// use and/or connectors ('First + Second, Third'):
			'search_with_connectors' => array('type' => 'like', 'field' => 'Article.title', 'connectorAnd' => '+', 'connectorOr' => ',')
		);

### `emptyValue` default values to allow search for "not any of the below"

Let's say we have categories and a dropdown list to select any of those or "empty = ignore this filter". But what if we also want to have an option to find all non-categorized items?
With "default 0 NOT NULL" fields this works as we can use 0 here explicitly:

		$categories = $this->Model->Category->find('list');
		array_unshift($categories, '- not categorized -'); // before passing it on to the view (the key will be 0, not '' as the ignore-filter key will be)

But for char36 foreign keys or "default NULL" fields this does not work. The posted empty string will result in the omitting of the rule.
That's where `emptyValue` comes into play.

		public $presetVars = array(
			'category_id' => array(
				'allowEmpty' => true,
				'emptyValue' => '0',
			);
		);

This way we assign '' for 0, and "ignore" for '' on POST, and the opposite for presetForm().

Note: This only works if you use `allowEmpty` here. If you fail to do that it will always trigger the lookup here.

## Full example for model/controller configuration with overriding

	// model
	public $filterArgs = array(
		'some_related_table_id' => array('type' => 'value'),
		'search'=> array('type' => 'like', 'encode' => true, 'before' => false, 'after' => false, 'field' => array('ThisModel.name', 'OtherModel.name')),
		'name'=> array('type' => 'query', 'method' => 'searchNameCondition')
	);

	public function searchNameCondition($data = array()) {
		$filter = $data['name'];
		$cond = array(
			'OR' => array(
				$this->alias . '.name LIKE' => '' . $this->formatLike($filter) . '',
				$this->alias . '.invoice_number LIKE' => '' . $this->formatLike($filter) . '',
		));
		return $cond;
	}


	// controller (dry setup, only override/extend what is necessary)
	public $presetVars = array(
		'some_related_table_id' => true,
		'search' => true,
		'name'=> array( // overriding/extending the model defaults
			'type' => 'value',
			'encode' => true
		),
	);


	// search example with wildcards in the view for field `search`
	20??BE* => matches 2011BES and 2012BETR etc

## Behavior and Model configuration ##

All search fields need to be configured in the Model::filterArgs array.

Each filter record should contain array with several keys:

* name - the parameter stored in Model::data. In the example above the 'search' name used to search in the Article.description field (can be ommited if they key is the name).
* type - one of supported search types described below.
* field - Real field name used for search should be used.
* method - model method name or behavior used to generate expression, subquery or query.
* allowEmpty - optional parameter used for expression, subquery and query methods. It allow to generate condition even if filter field value is empty. It could used when condition generate based on several other fields. All fields data passed to method.

### Supported types of search ###

* 'like' or 'string'. This type of search used when you need to search using 'LIKE' sql keyword.
* 'value' or 'int'. This type of search very useful when you need exact compare. So if you have select box in your view as a filter than you definitely should  use value type.
* 'expression' type useful if you want to add condition that will generate by some method, and condition field contain several parameter like in previous sample used for 'range'. Field here contains 'Article.views BETWEEN ? AND ?' and Article::makeRangeCondition returns array of two values.
* 'subquery' type useful if you want to add condition that looks like FIELD IN (SUBQUERY), where SUBQUERY generated by method declared in this filter configuration.
* 'query' most universal type of search. In this case method should return array(that contain condition of any complexity). Returned condition will joined to whole search conditions.

## Post, redirect, get concept ##

Post/Redirect/Get (PRG) is a common design pattern for web developers to help avoid certain duplicate form submissions and allow user agents to behave more intuitively with bookmarks and the refresh button.

When a web form is submitted to a server through an HTTP POST request, a web user that attempts to refresh the server response in certain user agents can cause the contents of the original HTTP POST request to be resubmitted, possibly causing undesired results. To avoid this problem possible to use the PRG pattern instead of returning a web page directly, the POST operation returns a redirection command, instructing the browser to load a different page (or same page) using an HTTP GET request. See the [Wikipedia article](http://en.wikipedia.org/wiki/Post/Redirect/Get) for more information.

## PRG Component features ##

The Prg component implements the PRG pattern so you can use it separately from search tasks when you need it.

The component maintains passed and named parameters or query string variables that come as POST parameters and transform it to the named during redirect, and sets Controller::data back if the GET method was used during component call.

Most importantly the component acts as the glue between your app and the searchable behavior.

You can attach the component to your controller, here is an example
using defaults alreay set in the component itself:

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
			'allowlowedParams' => array(),
			'paramType' => 'named', // or 'querystring'
			'filterEmpty' => false
		)
	));

### Controller configuration ###

All search fields parameters need to configure in the Controller::presetVars array (if you didn't yet in the model).

Each preset variable is a array record that contains next keys:

* field      - field that defined in the view search form.
* type       - one of search types:
* value - should used for value that does not require any processing,
* checkbox - used for checkbox fields in view (Prg component pack and unpack checkbox values when pass it through the get named action).
* lookup - this type used when you have autocomplete lookup field implemented in your view. This lookup field is a text field, and also you have hidden field id value. In this case component will fill both text and id values.
* model      - param that specifies what model used in Controller::data at a key for this field.
* formField  - field in the form that contain text, and will populated using model.modelField based on field value.
* modelField - field in the model that contain text, and will used to fill formField in view.
* encode     - boolean, by default false. If you want to use search strings in URL's with special characters like % or / you need to use encoding
* empty     - boolean, by default false. If you want to omit this field in the PRG url if no value has been given (shorter urls).

Note: Those can also be configured in the model itself (to keep it DRY). You can then set `$presetVar = true` then in the controller to use the model ones (see the example above). You can still use define the keys here where you want to overwrite certain settings.
It is recommended to always use `encode => true` in combination with search strings (custom text input) to avoid url-breaking.

### Prg::commonProcess method usage ###

The `commonProcess` method defined in the Prg component allows you to inject search in any index controller with just 1-2 lines of additional code.

You should pass model name that used for search. By default it is default Controller::modelClass model.

Additional options parameters:

* form        - search form name.
* keepPassed  - parameter that describe if you need to merge passedArgs to Get url where you will Redirect after Post
* action      - sometimes you want to have different actions for post and get. In this case you can define get action using this parameter.
* modelMethod - method, used to filter named parameters, passed from form. By default it is validateSearch, and it defined in Searchable behavior.

## Requirements ##

* PHP version: PHP 5.2+
* CakePHP version: Cakephp 2.1 Stable

## Support ##

For more information about our Professional CakePHP Services please visit the [Cake Development Corporation website](http://cakedc.com).

## Branch strategy ##

The master branch holds the STABLE latest version of the plugin.
Develop branch is UNSTABLE and used to test new features before releasing them.

Previous maintenance versions are named after the CakePHP compatible version, for example, branch 1.3 is the maintenance version compatible with CakePHP 1.3.
All versions are updated with security patches.

## Contributing to this Plugin ##

Please feel free to contribute to the plugin with new issues, requests, unit tests and code fixes or new features. If you want to contribute some code, create a feature branch from develop, and send us your pull request. Unit tests for new features and issues detected are mandatory to keep quality high.


## License ##

Copyright 2009-2012, [Cake Development Corporation](http://cakedc.com)

Licensed under [The MIT License](http://www.opensource.org/licenses/mit-license.php)<br/>
Redistributions of files must retain the above copyright notice.

## Copyright ###

Copyright 2009-2012<br/>
[Cake Development Corporation](http://cakedc.com)<br/>
1785 E. Sahara Avenue, Suite 490-423<br/>
Las Vegas, Nevada 89104<br/>
http://cakedc.com<br/>
