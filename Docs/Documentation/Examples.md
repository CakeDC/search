Examples
========

An example of how to implement complex search conditions in your application.

Here is the model code.

```php
class Article extends AppModel {

	public $actsAs = array(
		'Search.Searchable'
	);

	public $belongsTo = array(
		'User'
	);

	public $hasAndBelongsToMany = array(
		'Tag' => array(
			'with' => 'Tagged'
		)
	);

	public $filterArgs = array(
		'title' => array(
			'type' => 'like'
		),
		'status' => array(
			'type' => 'value'
		),
		'blog_id' => array(
			'type' => 'lookup',
			'formField' => 'blog_input',
			'modelField' => 'title',
			'model' => 'Blog'
		),
		'search' => array(
			'type' => 'like',
			'field' => 'Article.description'
		),
		'range' => array(
			'type' => 'expression',
			'method' => 'makeRangeCondition',
			'field' => 'Article.views BETWEEN ? AND ?'
		),
		'username' => array(
			'type' => 'like', 'field' => array(
				'User.username',
				'UserInfo.first_name'
			)
		),
		'tags' => array(
			'type' => 'subquery',
			'method' => 'findByTags',
			'field' => 'Article.id'
		),
		'filter' => array(
			'type' => 'query',
			'method' => 'orConditions'
		),
		'year' => array(
			'type' => 'query',
			'method' => 'yearRange'
		),
		'enhanced_search' => array(
			'type' => 'like',
			'encode' => true,
			'before' => false,
			'after' => false,
			'field' => array(
				'ThisModel.name', 'OtherModel.name'
			)
		),
	);

	public function findByTags($data = array()) {
		$this->Tagged->Behaviors->attach('Containable', array(
				'autoFields' => false
			)
		);

		$this->Tagged->Behaviors->attach('Search.Searchable');
		$query = $this->Tagged->getQuery('all', array(
			'conditions' => array(
				'Tag.name' => $data['tags']
			),
			'fields' => array(
				'foreign_key'
			),
			'contain' => array(
				'Tag'
			)
		));
		return $query;
	}

	// Or conditions with like
	public function orConditions($data = array()) {
		$filter = $data['filter'];
		$condition = array(
			'OR' => array(
				$this->alias . '.title LIKE' => '%' . $filter . '%',
				$this->alias . '.body LIKE' => '%' . $filter . '%',
			)
		);
		return $condition;
	}

	// Turns 2000 - 2014 into a search between these two years
	public function yearRange($data = array()) {
		if (strpos($data['year'], ' - ') !== false){
			$tmp = explode(' - ', $data['year']);
			$tmp[0] = $tmp[0] . '-01-01';
			$tmp[1] = $tmp[1] . '-12-31';
			return $tmp;
		} else {
			return array($data['year'] . '-01-01', $data['year']."-12-31");
		}
	}
}
```

Associated snippet for the controller class.

```php
class ArticlesController extends AppController {

	public $components = array(
		'Search.Prg'
	);

	public function find() {
		$this->Prg->commonProcess();
		$this->Paginator->settings['conditions'] = $this->Article->parseCriteria($this->Prg->parsedParams());
		$this->set('articles', $this->Paginator->paginate());
	}
}
```

or verbose (overriding the model configuration)

```php
class ArticlesController extends AppController {

	public $components = array(
		'Search.Prg'
	);

	// This will override the model config
	public $presetVars = array(
		'title' => array(
			'type' => 'value'
		),
		'status' => array(
			'type' => 'checkbox'
		),
		'blog_id' => array(
			'type' => 'lookup',
			'formField' => 'blog_input',
			'modelField' => 'title',
			'model' => 'Blog'
		)
	);

	public function find() {
		$this->Prg->commonProcess();
		$this->Paginator->settings['conditions'] = $this->Article->parseCriteria($this->Prg->parsedParams());
		$this->set('articles', $this->Paginator->paginate());
	}
}
```

The ```find.ctp``` view is the same as ```index.ctp``` with the addition of the search form.

```php
echo $this->Form->create('Article', array(
	'url' => array_merge(
			array(
				'action' => 'find'
			),
			$this->params['pass']
		)
	)
);
echo $this->Form->input('title', array(
		'div' => false
	)
);
echo $this->Form->input('year', array(
		'div' => false
	)
);
echo $this->Form->input('blog_id', array(
		'div' => false,
		'options' => $blogs
	)
);
echo $this->Form->input('status', array(
		'div' => false,
		'multiple' => 'checkbox',
		'options' => array(
			'open', 'closed'
		)
	)
);
echo $this->Form->input('username', array(
		'div' => false
	)
);
echo $this->Form->submit(__('Search'), array(
		'div' => false
	)
);
echo $this->Form->end();
```

In this example the search by OR condition is shown. For this purpose we defined the method ```orConditions()``` and added the filter method.

```php
array(
	'name' => 'filter',
	'type' => 'query',
	'method' => 'orConditions'
)
```

Advanced Usage
--------------

```php
public $filterArgs = array(

	// match results with `%searchstring`:
	'search_exact_beginning' => array(
		'type' => 'like',
		'encode' => true,
		'before' => true,
		'after' => false
	),

	// match results with `searchstring%`:
	'search_exact_end' => array(
		'type' => 'like',
		'encode' => true,
		'before' => false,
		'after' => true
	),

	// match results with `__searchstring%`:
	'search_special_like' => array(
		'type' => 'like',
		'encode' => true,
		'before' => '__',
		'after' => '%'
	),

	// use custom wildcards in the frontend (instead of * and ?):
	'search_custom_like' => array(
		'type' => 'like',
		'encode' => true,
		'before' => false,
		'after' => false,
		'wildcardAny' => '%', 'wildcardOne' => '_'
	),

	// use and/or connectors ('First + Second, Third'):
	'search_with_connectors' => array(
		'type' => 'like',
		'field' => 'Article.title',
		'connectorAnd' => '+', 'connectorOr' => ','
	)
);
```

Default Values to Allow Search for "Not Any of The Below"
---------------------------------------------------------

Let's say we have categories and a dropdown list to select any of those or "empty = ignore this filter". But what if we also want to have an option to find all non-categorized items? With "default 0 NOT NULL" fields this works as we can use 0 here explicitly.

```php
$categories = $this->Model->Category->find('list');

// before passing it on to the view (the key will be 0, not '' as the ignore-filter key will be)
array_unshift($categories, '- not categorized -');
```

But for char(36) foreign keys or "default NULL" fields this doesn't work. The posted empty string will result in the omitting of the rule. That's where ```emptyValue``` comes into play.

```php
// controller
public $presetVars = array(
	'category_id' => array(
		'allowEmpty' => true,
		'emptyValue' => '0',
	);
);
```

This way we assign '' for 0, and "ignore" for '' on POST, and the opposite for ```presetForm()```.

Note: This only works if you use ```allowEmpty``` here. If you fail to do that it will always trigger the lookup here.

Default Values to Allow Search in Default Case
----------------------------------------------

The filterArgs property in your model.

```php
public $filterArgs = array(
	'some_related_table_id' => array(
		'type' => 'value',
		'defaultValue' => 'none'
	)
);
```

This will always trigger the filter for it (looking for string ```none``` in the table field).

Full Example for Model/Controller Configuration with Overriding
---------------------------------------------------------------

This goes in a model.

```php
public $filterArgs = array(
	'some_related_table_id' => array(
		'type' => 'value'
	),
	'search'=> array(
		'type' => 'like',
		'encode' => true,
		'before' => false,
		'after' => false,
		'field' => array(
			'ThisModel.name',
			'OtherModel.name'
		)
	),
	'name'=> array(
		'type' => 'query',
		'method' => 'searchNameCondition'
	)
);

public function searchNameCondition($data = array()) {
	$filter = $data['name'];
	$conditions = array(
		'OR' => array(
			$this->alias . '.name LIKE' => '' . $this->formatLike($filter) . '',
			$this->alias . '.invoice_number LIKE' => '' . $this->formatLike($filter) . '',
		)
	);
	return $conditions;
}
```

In your controller.

```php
public $presetVars = array(
	'some_related_table_id' => true,
	'search' => true,
	// overriding/extending the model defaults
	'name'=> array(
		'type' => 'value',
		'encode' => true
	),
);
```

Search example with wildcards in the view for field `search` 20??BE* => matches 2011BES and 2012BETR etc.

